import os
import json
import mysql.connector
from datetime import datetime

# --- 1. MySQL-Verbindung ---
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="bahn"
)
cursor = conn.cursor()

# --- 2. Pfad zu deinem JSON-Ordner ---
folder_path = "Oktober"

# --- 3. Alle JSON-Dateien durchgehen ---
for filename in os.listdir(folder_path):
    if filename.endswith(".json"):
        file_path = os.path.join(folder_path, filename)
        with open(file_path, "r", encoding="utf-8") as f:
            data = json.load(f)
            
            # --- 4. Datum umwandeln von DD.MM.YYYY -> YYYY-MM-DD ---
            datum_str = data.get("datum")
            datum_mysql = datetime.strptime(datum_str, "%d.%m.%Y").strftime("%Y-%m-%d")
            
            # --- 5. Einfügen in die MySQL-Tabelle ---
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

# --- 6. Änderungen speichern und Verbindung schließen ---
conn.commit()
cursor.close()
conn.close()

print("Alle JSON-Dateien erfolgreich in MySQL importiert!")
