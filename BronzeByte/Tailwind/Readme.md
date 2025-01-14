

Adding Tailwind CSS Configuration to Your Magento Theme
To enable and configure Tailwind CSS in your Magento theme, follow these steps:
1. Tailwind Configuration File (tailwind.config.js)
Ensure your tailwind.config.js includes the following configuration:
-------------------------------------------------------------------------------------------------------------
module.exports = {
  content: [
    './app/design/frontend/BronzeByte/Tailwind/**/*.{phtml,js,xml}', 
    './vendor/**/*.js', 
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
----------------------------------------------------------------------------------------------------------
2. Theme-Specific Tailwind Configuration (theme.xml)
Add the following <tailwind> section to your theme.xml file in your theme directory:
-----------------------------------------------------------------------------------------------------------
    <tailwind>
        <bronze_byte_tailwind>true</bronze_byte_tailwind>
        <tailwind_input_file>web/css/input.css</tailwind_input_file>
        <tailwind_output_file>web/css/tailwind.css</tailwind_output_file>
        <tailwind_config_file>tailwind.config.js</tailwind_config_file>
    </tailwind>
-----------------------------------------------------------------------------------------------------------
bronze_byte_tailwind: Set to true to enable Tailwind for this theme.
tailwind_input_file: Path to the Tailwind input CSS file (input.css).
tailwind_output_file: Path where the processed Tailwind CSS will be saved (tailwind.css).
tailwind_config_file: Path to the Tailwind configuration file (tailwind.config.js).
