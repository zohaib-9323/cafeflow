import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle';
import 'swagger-ui-dist/swagger-ui.css';

SwaggerUIBundle({
    url: document.getElementById('swagger').dataset.spec,
    dom_id: '#swagger',
    deepLinking: true,
    tryItOutEnabled: false,
    persistAuthorization: false,
    defaultModelsExpandDepth: 0,
});
