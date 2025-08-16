# Asistente Personal Híbrido con IA (v2.0)

Un asistente personal conversacional avanzado construido con PHP y Python. Este proyecto demuestra un sistema de IA de **dos cerebros** que separa la clasificación de intenciones de la extracción de entidades.

1.  **Cerebro 1 (PHP-ML):** Un modelo local que predice la **intención** general del usuario (`añadir_tarea`, `listar_tareas`, etc.).
2.  **Cerebro 2 (Python/spaCy):** Un modelo de **Reconocimiento de Entidades Nombradas (NER)** personalizado y entrenado por el usuario, que extrae los **detalles** de una frase (`TAREA`).

El sistema incluye un "Gimnasio de IA" para el entrenamiento del cerebro NER y un sistema de feedback en el chat principal para mejorar el cerebro de intenciones.

## ✨ Características Principales

*   **Interfaz de Chat Conversacional:** Con botones interactivos para una experiencia de usuario fluida.
*   **Cerebro de Intenciones (PHP):** Un modelo `NaiveBayes` entrenado para clasificar comandos.
*   **Cerebro de Entidades (Python):** Un modelo `spaCy` personalizado que aprende a extraer tareas a partir de los ejemplos del usuario.
*   **Gimnasio de IA (`entrenamiento.html`):** Una herramienta de desarrollo dedicada para crear datos de entrenamiento de alta calidad para el cerebro de entidades.
*   **Aprendizaje Activo Doble:** Dos bucles de feedback para mejorar ambos cerebros de forma independiente.

## 🛠️ Tecnologías Utilizadas

*   **Backend Principal:** PHP 8+
*   **Cerebro de IA (NER):** Python 3
*   **Base de Datos:** MySQL
*   **Librerías Clave:**
    *   PHP: `BotMan`, `PHP-ML`, `Carbon`
    *   Python: `spaCy`, `mysql-connector-python`

## 🚀 Cómo Ponerlo en Marcha

1.  **Clonar el Repositorio.**
2.  **Configurar la Base de Datos** (Crear BD, usuario e importar la estructura de las tablas `tareas`, `feedback_log`, y `ner_training_data`).
3.  **Instalar Dependencias de PHP:** `composer install`
4.  **Configurar el Entorno de Python:**
    ```bash
    python3 -m venv venv
    source venv/bin/activate
    pip install spacy mysql-connector-python
    python -m spacy download es_core_news_lg
    ```
5.  **Configurar Claves y BD** en `chatbot.php`.
6.  **Entrenar los Modelos de IA:**
    ```bash
    # Entrenar el clasificador de intenciones
    php entrenar_modelo.php
    
    # Entrenar el extractor de entidades (después de añadir datos en el gimnasio)
    source venv/bin/activate
    python train_ner.py
    ```
7.  ¡Listo! Lanza el proyecto en tu servidor web.
