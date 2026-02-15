const apiKey = "2e3bf8e9ebada1d8357635243838bb24";

document.getElementById("ladeBtn").addEventListener("click", ladeWetter);

function ladeWetter() {
  const stadt = document.getElementById("stadtInput").value;
  if (!stadt) return alert("Bitte eine Stadt eingeben!");

  fetch(`https://api.openweathermap.org/data/2.5/weather?q=${stadt}&appid=${apiKey}&units=metric&lang=de`)
    .then(res => res.json())
    .then(daten => {
      if (daten.cod !== 200) return alert("Stadt nicht gefunden");

      document.getElementById("stadtName").textContent = daten.name;
      document.getElementById("beschreibung").textContent = daten.weather[0].description;
      document.getElementById("temperatur").textContent = `🌡️ ${daten.main.temp} °C`;
      document.getElementById("wind").textContent = `💨 ${daten.wind.speed} m/s`;

      const iconCode = daten.weather[0].icon;
      document.getElementById("wetterIcon").src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;

      document.getElementById("wetterCard").style.display = "block";
    })
    .catch(err => {
      console.error(err);
      alert("Fehler beim Laden der Wetterdaten");
    });
}
