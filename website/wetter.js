const button = document.getElementById("btnWetter");
const output = document.getElementById("weatherOutput");

// 🔑 HIER DEIN API-KEY
const API_KEY = "2e3bf8e9ebada1d8357635243838bb24";
const CITY = "Berlin";

button.addEventListener("click", ladeWetter);

async function ladeWetter() {
    output.textContent = "Lade Wetterdaten...";

    try {
        const response = await fetch(
            `https://api.openweathermap.org/data/2.5/weather?q=${CITY}&units=metric&lang=de&appid=${API_KEY}`
        );

        if (!response.ok) {
            throw new Error("Wetterdaten konnten nicht geladen werden");
        }

        const data = await response.json();

        const temperatur = data.main.temp;
        const beschreibung = data.weather[0].description;
        const stadt = data.name;

        output.innerHTML = `
            🌍 <strong>${stadt}</strong><br>
            🌡️ Temperatur: ${temperatur} °C<br>
            ☁️ Wetter: ${beschreibung}
        `;

    } catch (error) {
        output.textContent = "❌ Fehler beim Laden der Wetterdaten";
        console.error(error);
    }
}


/*function ladeWetter() {
  const apiKey = "";

 fetch(`https://api.openweathermap.org/data/2.5/weather?q=Berlin&appid=${apiKey}&units=metric&lang=de`)
    .then(response => {
      console.log("Status:", response.status);
      return response.json();
    })
    .then(daten => {
      console.log("API Antwort:", daten);

      if (daten.cod !== 200) {
        alert("API-Fehler: " + daten.message);
        return;
      }

      document.getElementById("output").textContent =
        `Temperatur: ${daten.main.temp} °C
Wetter: ${daten.weather[0].description}`;
    })
    .catch(error => {
      console.error("Fetch-Fehler:", error);
      alert("Netzwerkfehler");
    });
}*/