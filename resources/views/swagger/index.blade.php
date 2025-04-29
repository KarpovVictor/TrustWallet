<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Crypto Wallet API</title>
  <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui.css">
  <style>
    html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
    *, *:before, *:after { box-sizing: inherit; }
    body { margin: 0; background: #fafafa; }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>

  <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-bundle.js"></script>
  <script src="https://unpkg.com/swagger-ui-dist@4.5.0/swagger-ui-standalone-preset.js"></script>
  <script>
    window.onload = function() {
      // Получаем токен из URL
      const urlParams = new URLSearchParams(window.location.search);
      const token = urlParams.get('token');
      
      // Формируем URL с токеном
      let yamlUrl = "docs/swagger.yaml"; // Обратите внимание на путь
      if (token) {
        yamlUrl += "?token=" + token;
      }
      
      // Инициализация Swagger UI
      const ui = SwaggerUIBundle({
        url: yamlUrl,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "BaseLayout", // Изменено с StandaloneLayout на BaseLayout
        defaultModelsExpandDepth: 1,
        displayRequestDuration: true
      });
      window.ui = ui;
    };
  </script>
</body>
</html>