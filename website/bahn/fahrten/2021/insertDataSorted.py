import os
import json
import mysql.connector
from datetime import datetime
import re

# --- 1. MySQL-Verbindung ---
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="bahn"
)
cursor = conn.cursor()

# --- 2. Ordner mit JSON-Dateien (relativ zum Skript) ---
folder_path = "Oktober"

# --- 3. Prüfen, ob Ordner existiert ---
if not os.path.exists(folder_path):
    print(f"Ordner nicht gefunden: {folder_path}")
    exit()

# --- 4. Alle JSON-Dateien im Ordner ---
files = [f for f in os.listdir(folder_path) if f.endswith(".json")]

if not files:
    print("Keine JSON-Dateien im Ordner gefunden!")
    exit()

# --- 5. Numerisch nach führender Zahl sortieren ---
def extract_number(filename):
    match = re.match(r"(\d+)_", filename)
    return int(match.group(1)) if match else float('inf')

files_sorted = sorted(files, key=extract_number)
print("Sortierte Dateien:", files_sorted)

# --- 6. Alle Dateien einlesen und in MySQL importieren ---
for filename in files_sorted:
    file_path = os.path.join(folder_path, filename)
    try:
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)

            # Datum von DD.MM.YYYY -> YYYY-MM-DD
            datum_str = data.get("datum")
            datum_mysql = datetime.strptime(datum_str, "%d.%m.%Y").strftime("%Y-%m-%d")

            # SQL-Insert
            sql = """
            INSERT INTO fahrten 
            (bugtitel, zugart, datum, vz_nummer, tz_nummer, wagennummer, klasse, abfahrtsort, abfahrtszeit, ankunftsort, ankunftszeit)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            values = (
                data.get("bugtitel"),
                data.get("zugart"),
                datum_mysql,
                data.get("vz_nummer"),
                data.get("tz_nummer"),
                data.get("wagennummer"),
                data.get("klasse"),
                data.get("abfahrtsort"),
                data.get("abfahrtszeit"),
                data.get("ankunftsort"),
                data.get("ankunftszeit")
            )
            cursor.execute(sql, values)
    except Exception as e:
        print(f"Fehler beim Verarbeiten von {filename}: {e}")

# --- 7. Änderungen speichern und Verbindung schließen ---
conn.commit()
cursor.close()
conn.close()

print("Alle JSON-Dateien erfolgreich in MySQL importiert!")
