import json
import mysql.connector
import datetime



# JSON-Datei einlesen
with open('IC_Fahrt_02-10-2021.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

data["datum"] = datetime.datetime.strptime(data["datum"], "%d.%m.%Y").date()

# Verbindung zur MySQL-Datenbank herstellen
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="bahn"
)

cursor = conn.cursor()

# SQL-Insert-Befehl vorbereiten
sql = """
INSERT INTO ic_fahrten (
    bugTitel, datum, tzNummer, wagennummer, klasse, abfahrtsort, abfahrtszeit, ankunftsort, ankunftszeit
) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
"""

values = (
    data["bugTitel"],
    data["datum"],
    data["tzNummer"],
    data["wagennummer"],
    data["klasse"],
    data["abfahrtsort"],
    data["abfahrtszeit"],
    data["ankunftsort"],
    data["ankunftszeit"]
)

# Einfügen
cursor.execute(sql, values)
conn.commit()

print(f"{cursor.rowcount} Datensatz erfolgreich eingefügt.")

# Verbindung schließen
cursor.close()
conn.close()
