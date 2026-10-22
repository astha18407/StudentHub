const apiKey = "2c4822a3cc5e3877435f531d3f2577d5";

const cityInput = document.getElementById("cityInput");
const weatherButton = document.getElementById("weatherButton");
const weatherBox = document.getElementById("weather");

weatherButton.addEventListener("click", function(){

const city = cityInput.value;

if(city === ""){

weatherBox.innerHTML = "<p>Please enter a city name.</p>";
return;

}

const url = `https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`;

weatherBox.innerHTML = "<p>Loading weather...</p>";

fetch(url)
.then(response => response.json())
.then(data => {

if(data.cod !== 200){

weatherBox.innerHTML = "<p>City not found.</p>";
return;

}

const temperature = data.main.temp;
const description = data.weather[0].description;
const icon = data.weather[0].icon;

weatherBox.innerHTML = `
<img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="${description}">
<h3>${data.name}</h3>
<p>${temperature} °C</p>
<p>${description}</p>
`;

})
.catch(error => {

weatherBox.innerHTML = "<p>Weather could not be loaded.</p>";

});

});