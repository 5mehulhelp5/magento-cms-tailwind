<?php
namespace BronzeByte\Tailwind\Model;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Psr\Log\LoggerInterface;

class Builder
{
    const RELEASE = "https://api.github.com/repos/tailwindlabs/tailwindcss/releases/latest";
    const REPOSITIRY = "https://github.com/tailwindlabs/tailwindcss/releases/download/%s/%s";

    
    public static $version = null;
    public static $binary = null;
    protected $scopeConfig;
    protected $storeManager;
    protected $themeProvider;
    protected $logger;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ThemeProviderInterface $themeProvider,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->themeProvider = $themeProvider;
        $this->logger = $logger;
    }


    public function build(?string $html = null,?string $version = null): string {

        try {
            
            $themeData = $this->getTheme(); 

            $this->logger->debug("Theme Data: " . print_r($themeData, true));

            if (
                empty($themeData["tailwind"]["tailwind_input_file"]) ||
                empty($themeData["tailwind"]["tailwind_output_file"]) ||
                empty($themeData["tailwind"]["tailwind_config_file"])
            ) {
                throw new \Exception(
                    "Tailwind input, output, or config file path is not defined in the theme settings."
                );
            }
                $themeId = $this->scopeConfig->getValue(
                    \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->storeManager->getStore()->getId()
                );

            $this->logger->debug("Using Theme ID: " . $themeId);

            $theme = $this->themeProvider->getThemeById($themeId);

            $this->logger->debug("Theme Path: " . $theme->getThemePath());

            $inputFile = $themeData["tailwind"]["tailwind_input_file"];
            $outputFile = $themeData["tailwind"]["tailwind_output_file"];
            $configFile = $themeData["tailwind"]["tailwind_config_file"];

            $themeXmlPath = BP ."/app/design/frontend/" .$theme->getThemePath() . "/" .$inputFile;
            $outputXmlPath =BP ."/app/design/frontend/" .$theme->getThemePath() . "/" .$outputFile;
            $configXmlPath =BP ."/app/design/frontend/" .$theme->getThemePath() ."/"  .$configFile;

            // Log all paths for debugging
            $this->logger->debug("Input File Path: " . $themeXmlPath);
            $this->logger->debug("Output File Path: " . $outputXmlPath);
            $this->logger->debug("Config File Path: " . $configXmlPath);

            if (!file_exists($themeXmlPath)) {
         
                self::write(
                    $themeXmlPath,
                    $html = "@tailwind base;
                             @tailwind components;
                             @tailwind utilities;"
                );
                $this->logger->debug(
                    "Written HTML to input file: " . $themeXmlPath
                );
            } else {
                $this->logger->debug(
                    "Input file already exists, skipping creation: " .
                        $themeXmlPath
                );
            }

            if (!file_exists($outputXmlPath)) {
       
                $this->logger->debug("Cleared output file: " . $outputXmlPath);
            } else {
                $this->logger->debug(
                    "Output file already exists, skipping clearing: " .
                        $outputXmlPath
                );
            }

            $cmd = [
                self::getCli($version),
                "--no-autoprefixer",
                "-c",
                $configXmlPath,
                "-i",
                $themeXmlPath,
                "-o",
                $outputXmlPath,
                "--minify",
            ];

            $this->logger->debug("Tailwind Command: " . implode(" ", $cmd));

            $tailwindcss = new Process($cmd);
            $status = $tailwindcss->run();

            if ($status !== 0) {
                $errorOutput = str_replace(
                    "\n",
                    "\\A",
                    $tailwindcss->getErrorOutput()
                );
                $this->logger->error(
                    "Tailwind CSS Process Error: " . $errorOutput
                );
                throw new \Exception($errorOutput);
            }

            $this->logger->debug("Tailwind CSS build completed successfully.");

            return file_get_contents($outputXmlPath);
        } catch (\Exception $e) {
            // Log error
            $this->logger->error(
                "Error during Tailwind build: " . $e->getMessage()
            );
            throw $e;
        }
    }

    public static function getCli(?string $version = null): string
    {
        if (empty($version)) {
            $version = self::getLatestVersion();
        }

        $lib = dirname(__DIR__) . "/bin/" . $version;

        $binaryName = self::getBinaryName();

        if (!file_exists($lib . "/" . $binaryName)) {
            self::downloadExecutable($version);
        }

        return $lib . "/" . $binaryName;
    }

    private static function getLatestVersion(): string
    {
        if (empty(self::$version)) {
            try {
                $curl = HttpClient::create();
                $response = $curl->request("GET", self::RELEASE);

                if (isset($response->toArray()["name"])) {
                    self::$version = $response->toArray()["name"];
                } else {
                    throw new \Exception(
                        "Cannot get the latest version name from response JSON."
                    );
                }
            } catch (\Throwable $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$version;
    }

    private static function downloadExecutable(?string $version = null): void
    {
        if (empty($version)) {
            $version = self::getLatestVersion();
        }

        $binaryName = self::getBinaryName();
        $url = sprintf(self::REPOSITIRY, $version, $binaryName);

        $lib = dirname(__DIR__) . "/bin/" . $version;

        try {
            $curl = HttpClient::create();
            $response = $curl->request("GET", $url);
            $content = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \InvalidArgumentException(
                "Version is not exists:" . $e->getMessage()
            );
        }

        //Create dir bin or version if not exists
        self::mkdir(dirname(__DIR__) . "/bin/");
        self::mkdir($lib);

        self::write($lib . "/" . $binaryName, $content);

        chmod($lib . "/" . $binaryName, 0777);
    }

    /**
     * @internal
     */
    public static function getBinaryName(): string
    {
        if (empty(self::$binary)) {
            $os = strtolower(\PHP_OS);
            $machine = strtolower(php_uname("m"));

            switch (true) {
                case str_contains($os, "darwin"):
                    switch ($machine) {
                        case "arm64":
                            self::$binary = "tailwindcss-macos-arm64";
                            break;
                        case "x86_64":
                            self::$binary = "tailwindcss-macos-x64";
                            break;
                        default:
                            throw new \Exception(
                                sprintf(
                                    "No matching machine found for Darwin platform (Machine: %s).",
                                    $machine
                                )
                            );
                    }
                    break;
                case str_contains($os, "linux"):
                    switch ($machine) {
                        case "arm64":
                        case "aarch64":
                            self::$binary = "tailwindcss-linux-arm64";
                            break;
                        case "armv7":
                            self::$binary = "tailwindcss-linux-armv7";
                            break;
                        case "x86_64":
                            self::$binary = "tailwindcss-linux-x64";
                            break;
                        default:
                            throw new \Exception(
                                sprintf(
                                    "No matching machine found for Linux platform (Machine: %s).",
                                    $machine
                                )
                            );
                    }
                    break;
                case str_contains($os, "win"):
                    switch ($machine) {
                        case "arm64":
                            self::$binary = "tailwindcss-windows-arm64.exe";
                            break;
                        case "x86_64":
                        case "amd64":
                            self::$binary = "tailwindcss-windows-x64.exe";
                            break;
                        default:
                            throw new \Exception(
                                sprintf(
                                    "No matching machine found for Windows platform (Machine: %s).",
                                    $machine
                                )
                            );
                    }
                    break;
                default:
                    throw new \Exception(
                        sprintf(
                            "Unknown platform or architecture (OS: %s, Machine: %s).",
                            $os,
                            $machine
                        )
                    );
            }
        }
        return self::$binary;
    }

    private static function write($path, $content)
    {
        $result = file_put_contents($path, $content);
        if ($result === false) {
            throw new \Exception("Directory is not writable: " . $path);
        }
    }
    private static function mkdir($path)
    {
        if (!is_dir($path)) {
            $result = mkdir($path, 0777, true);
            if ($result === false) {
                throw new \Exception("Directory is not writable: " . $lib);
            }
        }
    }

    public static function postInstall(): void
    {
        self::downloadExecutable();
    }
    public static function postUpdate(): void
    {
        self::downloadExecutable();
    }

    private function getTheme()
    {
        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        /** @var $theme \Magento\Framework\View\Design\ThemeInterface */
        $theme = $this->themeProvider->getThemeById($themeId);

        if ($theme) {
            $themeData = $theme->getData();
            $themeXmlPath =
                BP .
                "/app/design/frontend/" .
                $theme->getThemePath() .
                "/theme.xml";
            if (file_exists($themeXmlPath)) {
                $themeXml = simplexml_load_file($themeXmlPath);
                $tailwindSettings = $this->parseTailwindSettings($themeXml);

                return array_merge($themeData, [
                    "tailwind" => $tailwindSettings,
                ]);
            }
        }

        return [];
    }

    private function parseTailwindSettings($themeXml)
    {
        $tailwind = $themeXml->tailwind ?? null;
        if ($tailwind) {
            return [
                "bronze_byte_tailwind" => (string) $tailwind->bronze_byte_tailwind,
                "tailwind_input_file" => (string) $tailwind->tailwind_input_file,
                "tailwind_output_file" => (string) $tailwind->tailwind_output_file,
                "tailwind_config_file" => (string) $tailwind->tailwind_config_file,
            ];
        }

        return [];
    }

    public function echoThemeDetails()
    {
        try {
            $themeData = $this->getTheme();

            if (!empty($themeData)) {
                echo "Active Theme Data:\n";
                foreach ($themeData as $key => $value) {
                    if (is_array($value)) {
                        echo strtoupper($key) . ":\n";
                        foreach ($value as $subKey => $subValue) {
                            echo "  - {$subKey}: {$subValue}\n";
                        }
                    } else {
                        echo "{$key}: {$value}\n";
                    }
                }
                if (!empty($themeData["tailwind"])) {
                    echo "\nTailwind Settings:\n";
                    foreach ($themeData["tailwind"] as $key => $value) {
                        echo "  {$key}: {$value}\n";
                    }
                } else {
                    echo "\nNo Tailwind settings found in the current theme.\n";
                }
            } else {
                echo "No active theme found for the current store.\n";
            }
        } catch (\Exception $e) {
            echo "Error occurred while retrieving the theme: " .
                $e->getMessage() .
                "\n";
        }
    }
}
