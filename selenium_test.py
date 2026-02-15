from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Firefox starten
driver = webdriver.Firefox()

try:
    # Webseite öffnen
    driver.get("https://www.google.de")

    # Auf das Suchfeld warten
    search_box = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.NAME, "q"))
    )

    # Text eingeben und Suche abschicken
    search_box.send_keys("Selenium Firefox Test")
    search_box.submit()

    # Warten, bis Ergebnisse sichtbar sind
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "search"))
    )

    print("✅ Test erfolgreich: Google Suche läuft!")

finally:
    # Browser schließen
    driver.quit()

