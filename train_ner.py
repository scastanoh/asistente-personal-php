import mysql.connector
import spacy
from spacy.tokens import DocBin
from spacy.training import Example
import random

print("--- INICIANDO LA NOCHE DE ESTUDIO (ENTRENAMIENTO NER PERSONALIZADO) ---")

# 1. Cargar TODOS los datos del Gimnasio
try:
    db = mysql.connector.connect(
        host="localhost", user="asistente_user", password="Sanjose4$", database="asistente_ia_db"
    )
    cursor = db.cursor(dictionary=True)
    cursor.execute("SELECT texto_original, tarea_correcta FROM ner_training_data WHERE tarea_correcta IS NOT NULL AND tarea_correcta != ''")
    TRAINING_DATA = cursor.fetchall()
    # ... (cierre de conexión)
    print(f"Se han cargado {len(TRAINING_DATA)} ejemplos de TAREA desde la base de datos.")
except Exception as e:
    exit(f"Error al leer la base de datos: {e}")

# 2. Preparar los datos para que spaCy los entienda
spacy_training_data = []
for item in TRAINING_DATA:
    text = item['texto_original']
    task = item['tarea_correcta']
    start_index = text.lower().find(task.lower())
    if start_index != -1:
        end_index = start_index + len(task)
        entities = [(start_index, end_index, "TAREA")]
        spacy_training_data.append((text, {"entities": entities}))

if not spacy_training_data:
    exit("No hay datos válidos para entrenar.")

# 3. Entrenamiento del Modelo
print("Iniciando entrenamiento...")
nlp = spacy.load("es_core_news_lg")
ner = nlp.get_pipe("ner")
ner.add_label("TAREA")

pipe_exceptions = ["ner", "trf_wordpiecer", "trf_tok2vec"]
unaffected_pipes = [pipe for pipe in nlp.pipe_names if pipe not in pipe_exceptions]

with nlp.disable_pipes(*unaffected_pipes):
    optimizer = nlp.begin_training()
    for iteration in range(15): # 15 pasadas de estudio
        random.shuffle(spacy_training_data)
        losses = {}
        for text, annotations in spacy_training_data:
            try:
                doc = nlp.make_doc(text)
                example = Example.from_dict(doc, annotations)
                nlp.update([example], drop=0.5, sgd=optimizer, losses=losses)
            except: continue
        print(f"  Iteración {iteration + 1}/15 - Error: {losses.get('ner', 0.0)}")

# 4. Guardar el cerebro entrenado
output_dir = "ner_model"
nlp.to_disk(output_dir)
print(f"\n¡Entrenamiento completado! Modelo experto en TAREAS guardado en '{output_dir}'.")