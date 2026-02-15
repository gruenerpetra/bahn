import mysql.connector


fahrt_daten = {
    "bug_id": 332414,
    "zugtyp": "IC2 Dosto",
    "aussenmonitore": 1,
    "tz_nummer": 4883,
    "vz_nummer": 2067,
    "wagennummer": "50 80 26 - 81 557 -3",
    "klasse": 2,
    "teststandort": "Sitzplatz mittig im Wagen",
    "datum": "2021-10-02",
    "abfahrt_ort": "Stuttgart Hbf",
    "abfahrt_zeit": "12:08:00",
    "ankunft_ort": "Nürnberg Hbf",
    "ankunft_zeit": "14:18:00",
    "wlan_name": "WIFIonICE",
    "wlan_auto": True,
    "google_ladezeit_sec": 2
}




conn = mysql.connector.connect(
     host="localhost",
    user="root",
    password="",
    database="bahn"
)
print("Verbindung erfolgreich!")

cursor = conn.cursor()


sql = """
INSERT INTO ic_fahrten (
    bug_id, zugtyp, aussenmonitore, tz_nummer, vz_nummer,
    wagennummer, klasse, teststandort, datum,
    abfahrt_ort, abfahrt_zeit, ankunft_ort, ankunft_zeit,
    wlan_name, wlan_auto, google_ladezeit_sec
)
VALUES (
    %(bug_id)s, %(zugtyp)s, %(aussenmonitore)s, %(tz_nummer)s, %(vz_nummer)s,
    %(wagennummer)s, %(klasse)s, %(teststandort)s, %(datum)s,
    %(abfahrt_ort)s, %(abfahrt_zeit)s, %(ankunft_ort)s, %(ankunft_zeit)s,
    %(wlan_name)s, %(wlan_auto)s, %(google_ladezeit_sec)s
)
"""

cursor.execute(sql, fahrt_daten)
conn.commit()



cursor.close()
conn.close()

