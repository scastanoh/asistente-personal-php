import sys
import spacy
import json
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
model_path = os.path.join(script_dir, "ner_model")

try:
    nlp = spacy.load(model_path) 
except OSError:
    nlp = spacy.load("es_core_news_lg")

text_to_analyze = sys.argv[1]
doc = nlp(text_to_analyze)

entities = []
for ent in doc.ents:
    entities.append({'text': ent.text, 'type': ent.label_})

print(json.dumps(entities))