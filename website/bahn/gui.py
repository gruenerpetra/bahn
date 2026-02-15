import pandas as pd
import mysql.connector
import folium
from geopy.geocoders import Nominatim
from folium.plugins import MarkerCluster

# Verbindung zur MySQL-Datenbank
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="bahn"
)

cursor = conn.cursor(dictionary=True)
cursor.execute("SELECT * FROM fahrten")
rows = cursor.fetchall()
df = pd.DataFrame(rows)

cursor.close()
conn.close()
# query = "SELECT * FROM fahrten"
# df = pd.read_sql(query, conn)
# conn.close()

# Geocoder initialisieren
geolocator = Nominatim(user_agent="bahn_app")

# Funktion: Ort zu Koordinaten
def get_coordinates(place):
    try:
        location = geolocator.geocode(f"{place}, Deutschland")
        if location:
            return (location.latitude, location.longitude)
        else:
            return None
    except:
        return None

# Neue Spalten für Koordinaten
df['abfahrt_coords'] = df['abfahrtsort'].apply(get_coordinates)
df['ankunft_coords'] = df['ankunftsort'].apply(get_coordinates)

# Deutschlandkarte
karte = folium.Map(location=[51.1657, 10.4515], zoom_start=6)
marker_cluster = MarkerCluster().add_to(karte)

# Marker für Abfahrt und Ankunft + Linie
for idx, row in df.iterrows():
    if row['abfahrt_coords'] and row['ankunft_coords']:
        folium.Marker(location=row['abfahrt_coords'], popup=f"Abfahrt: {row['abfahrtsort']}").add_to(marker_cluster)
        folium.Marker(location=row['ankunft_coords'], popup=f"Ankunft: {row['ankunftsort']}").add_to(marker_cluster)
        folium.PolyLine([row['abfahrt_coords'], row['ankunft_coords']], color="blue", weight=2.5, opacity=0.7).add_to(karte)

# Karte speichern
karte.save("fahrten_karte.html")
print("Karte erstellt! Öffne fahrten_karte.html im Browser.")
