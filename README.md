# Asistente Personal Híbrido con IA

Un asistente personal conversacional basado en la web, construido desde cero con PHP y MySQL. Este proyecto demuestra un sistema de IA híbrido que utiliza un cerebro de Machine Learning local para tareas específicas y se conecta a la API de Google Gemini para conversaciones de conocimiento general.

## ✨ Características Principales

*   **Interfaz de Chat Limpia:** Una interfaz web simple y robusta construida con HTML, CSS y JavaScript.
*   **Cerebro de IA Local:** Utiliza `PHP-ML` para entrenar un modelo `NaiveBayes` que clasifica las intenciones del usuario (añadir, listar, completar, eliminar tareas).
*   **Conexión a IA Externa:** Se conecta a la API de **Google Gemini** (`gemini-1.5-flash-latest`) para manejar conversaciones de charla general, dotando al asistente de conocimiento del mundo.
*   **Gestión de Tareas:** Permite añadir, listar, completar y eliminar tareas a través de lenguaje natural.
*   **Aprendizaje Activo (Feedback Loop):** Incluye un sistema de calificación (✓/✗) que permite al desarrollador corregir los errores del modelo. Un script de re-entrenamiento (`reentrenar.php`) utiliza este feedback para mejorar la precisión de la IA con el tiempo.

## 🛠️ Tecnologías Utilizadas

*   **Backend:** PHP 8+
*   **Base de Datos:** MySQL
*   **Gestor de Dependencias:** Composer
*   **Librerías Clave de PHP:**
    *   `botman/botman`: Framework base para el chatbot.
    *   `php-ai/php-ml`: Para el entrenamiento y clasificación del modelo de IA local.
    *   `google-gemini-php/client`: Para la conexión con la API de Google Gemini.

## 🚀 Cómo Ponerlo en Marcha

1.  **Clonar el Repositorio:**
    ```bash
    git clone https://github.com/scastanoh/asistente-personal-php.git
    cd asistente-personal-php
    ```

2.  **Configurar la Base de Datos:**
    *   Crea una base de datos MySQL llamada `asistente_ia_db`.
    *   Crea un usuario (ej: `asistente_user`) con una contraseña y otórgale todos los permisos sobre la base de datos.
    *   Importa la estructura de las tablas `tareas` y `feedback_log` (puedes encontrar el SQL en `DOCUMENTACION.md`).

3.  **Instalar Dependencias:**
    ```bash
    composer install
    ```

4.  **Configurar las Claves:**
    *   Abre el archivo `chatbot.php`.
    *   Actualiza las variables de conexión a la base de datos (`$db_host`, `$db_name`, `$db_user`, `$db_pass`).
    *   Añade tu clave de la API de Google Gemini en la variable `$apiKey`.

5.  **Entrenar el Modelo de IA Inicial:**
    El proyecto incluye un archivo `training_data.csv` con cientos de ejemplos. Para crear tu primer modelo, ejecuta:
    ```bash
    php entrenar_modelo.php
    ```
    Esto creará el archivo `modelo_entrenado.phpml`.

6.  **¡Listo!** Lanza el proyecto en tu servidor web local (Apache, Nginx, etc.) y empieza a chatear.

## 📖 Estructura del Proyecto

*   `index.html`: El frontend de la aplicación de chat.
*   `chatbot.php`: El backend y cerebro principal que procesa todos los mensajes.
*   `training_data.csv`: El conjunto de datos para entrenar el modelo de IA local.
*   `entrenar_modelo.php`: Script para entrenar y guardar el modelo de IA.
*   `reentrenar.php`: Script para mejorar el modelo usando el feedback guardado en la base de datos.
*   `DOCUMENTACION.md`: Documentación técnica detallada de todo el proyecto.