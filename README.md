# Asistente Personal Híbrido con IA y Aprendizaje Interactivo (v3.0)

Un asistente personal conversacional avanzado que combina un sistema de reglas robusto con Inteligencia Artificial. Este proyecto demuestra una arquitectura híbrida donde un cerebro de IA (Python/spaCy) extrae la TAREA, mientras que un motor de reglas "descuartizador" en PHP extrae la FECHA y la HORA.

La característica más destacada es su **ciclo de feedback y re-entrenamiento interactivo**, que permite al usuario mejorar la IA directamente desde la interfaz de chat, transformando cada conversación en una oportunidad de aprendizaje.

- **Cerebro de Tareas (IA - Python/spaCy):** Un modelo de Reconocimiento de Entidades Nombradas (NER) personalizado que extrae la descripción de la tarea (`TAREA`).
- **Cerebro de Tiempo (Reglas - PHP):** Un motor "descuartizador" que identifica y aísla fragmentos de `FECHA` y `HORA`, junto a un "traductor" que los normaliza para su procesamiento.
- **Cerebro de Intenciones (PHP-ML):** Un modelo local que predice la intención general del usuario (`añadir_tarea`, `listar_tareas`, etc.).

## ✨ Características Principales (v3.0)

- **Interfaz de Chat Inteligente:** Conversación fluida con respuestas contextuales.
- **Sistema de Triple Botón para Tareas:**
  - **✅ Sí, guardar:** Confirma la tarea y **refuerza el conocimiento de la IA** guardando el acierto como un ejemplo de entrenamiento.
  - **✏️ No, corregir fecha:** Permite una corrección rápida de la fecha/hora para la tarea actual, sin afectar el entrenamiento.
  - **🧠 No, re-entrenar IA:** Abre un panel avanzado para enseñarle a la IA los fragmentos de texto correctos, mejorando su precisión futura.
- **Gimnasio de IA (`entrenamiento.html`):** Herramienta dedicada para crear datos de entrenamiento de alta calidad para el cerebro de entidades (`TAREA`, `FECHA`, `HORA`).
- **Feedback de Intenciones:** Botones (✓/✗) en las respuestas para mejorar el clasificador de intenciones.
- **Arquitectura Híbrida Robusta:** Combina la precisión de las reglas para el tiempo con la flexibilidad contextual de la IA para las tareas.

## 🛠️ Tecnologías Utilizadas

- **Backend Principal:** PHP 8+
- **Cerebro de IA (NER):** Python 3
- **Base de Datos:** MySQL
- **Librerías Clave:**
  - **PHP:** BotMan (manejo del chat), PHP-ML (clasificación de intenciones), Carbon (manejo de fechas).
  - **Python:** spaCy (NER), mysql-connector-python.

## 🚀 Cómo Ponerlo en Marcha

1.  **Clonar el Repositorio:**
    ```bash
    git clone https://github.com/scastanoh/asistente-personal-php.git
    cd asistente-personal-php
    ```

2.  **Configurar la Base de Datos:**
    - Crea una base de datos y un usuario en MySQL.
    - Importa la estructura de las tablas: `tareas`, `feedback_log`, y `ner_training_data`.
    - **Importante:** Asegúrate de que `ner_training_data` tenga las columnas `fecha_texto` y `hora_texto` (VARCHAR).

3.  **Instalar Dependencias de PHP:**
    ```bash
    composer install
    ```

4.  **Configurar el Entorno de Python:**
    ```bash
    python3 -m venv venv
    source venv/bin/activate
    # Se recomienda crear un archivo requirements.txt con 'spacy' y 'mysql-connector-python'
    pip install spacy mysql-connector-python
    python -m spacy download es_core_news_lg
    ```

5.  **Configurar Claves y BD:**
    - Edita `chatbot.php` y otros archivos relevantes con tus credenciales de base de datos.

6.  **Entrenar los Modelos de IA:**
    - **Paso A (Crucial):** Usa `entrenamiento.html` para añadir al menos 30-40 ejemplos de alta calidad, etiquetando TAREA, FECHA y HORA.
    - **Paso B:** Ejecuta los scripts de entrenamiento:
      ```bash
      # Entrenar el clasificador de intenciones
      php entrenar_modelo.php

      # Entrenar el extractor de entidades (TAREA)
      # Asegúrate de estar en el entorno virtual (source venv/bin/activate)
      python train_ner.py
      ```

7.  **¡Listo!** Lanza el proyecto en tu servidor web local (Apache, Nginx, etc.).
