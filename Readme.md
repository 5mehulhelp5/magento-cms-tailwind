# Adding Tailwind CSS Configuration to Your Magento Theme

This module facilitates the seamless integration of Tailwind CSS into your Magento theme, empowering you to leverage Tailwind's utility-first CSS framework for advanced styling and customizations. By incorporating this module, your Magento store will be able to apply Tailwind's versatile styles to CMS blocks, pages, and other frontend components, enhancing the overall design and user experience.
---

## 1. Tailwind Configuration File (`tailwind.config.js`)

Ensure your `tailwind.config.js` includes the following configuration:

```javascript
module.exports = {
  content: [
     './app/design/frontend/BronzeByte/base/**/*.html',
     './app/design/frontend/BronzeByte/base/**/*.phtml',
     './app/design/frontend/BronzeByte/base/**/*.js',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
```

- **content**: Specifies the paths to your theme files (e.g., `.phtml`, `.js`, `.xml`) where Tailwind CSS classes will be used.
- **theme.extend**: Allows you to extend the default Tailwind CSS theme.
- **plugins**: An array to include Tailwind plugins if required.

---

## 2. Theme-Specific Tailwind Configuration (`theme.xml`)

Add the following `<tailwind>` section to your `theme.xml` file in your theme directory:

```xml

    <tailwind>
        <tailwind_input_file>web/css/input.css</tailwind_input_file>
        <tailwind_output_file>web/css/tailwind.css</tailwind_output_file>
        <tailwind_config_file>tailwind.config.js</tailwind_config_file>
    </tailwind>

```

### Explanation of `<tailwind>` Configuration:

- **`bronze_byte_tailwind`**: Set to `true` to enable Tailwind CSS for this theme.
- **`tailwind_input_file`**: Path to the Tailwind input CSS file (`input.css`).
- **`tailwind_output_file`**: Path where the processed Tailwind CSS will be saved (`tailwind.css`).
- **`tailwind_config_file`**: Path to the Tailwind configuration file (`tailwind.config.js`).

---

By completing these steps, you will have successfully integrated and configured Tailwind CSS in your Magento theme. Tailwind will now process your styles based on the configuration provided and generate the output CSS file.
