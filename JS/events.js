let allEvents = [];
let currentEvents = [];
let currentPage = 1;
const eventsPerPage = 4;
const eventList =
    document.getElementById("eventList");
const eventSearch =
    document.getElementById("eventSearch");
const eventCategory =
    document.getElementById("eventCategory");
const eventSort =
    document.getElementById("eventSort");
const eventStatus =
    document.getElementById("eventStatus");
const pagination =
    document.getElementById("pagination");
fetch("data/events.json")
    .then(response => {
        if (!response.ok) {
            throw new Error("Unable to fetch events");
        }
        return response.json();
    })
    .then(events => {
        allEvents = events;
        currentEvents = events;
        displayEvents();
    })
    .catch(error => {
        console.log(error);
        eventStatus.innerHTML =
            "Unable to load events.";
        eventList.innerHTML = "";
    });
function displayEvents() {
    eventList.innerHTML = "";
    if (currentEvents.length === 0) {
        eventStatus.innerHTML =
            "No events found.";
        pagination.innerHTML = "";
        return;
    }
    eventStatus.innerHTML =
        currentEvents.length + " events found.";
    const start =
        (currentPage - 1) * eventsPerPage;
    const end =
        start + eventsPerPage;
    const pageEvents =
        currentEvents.slice(start, end);
    pageEvents.forEach(event => {
        eventList.innerHTML += `
            <div class="event-card">
                <h2>${event.title}</h2>
                <p>
                    <strong>Category:</strong>
                    ${event.category}
                </p>
                <p>
                    <strong>Date:</strong>
                    ${event.date}
                </p>
                <p>
                    <strong>Venue:</strong>
                    ${event.venue}
                </p>
                <p>
                    ${event.description}
                </p>
            </div>
        `;
    });
    createPagination();
}
function filterEvents() {
    const searchText =
        eventSearch.value.toLowerCase();
    const category =
        eventCategory.value;
    currentEvents =
        allEvents.filter(event => {
            const matchesSearch =
                event.title
                    .toLowerCase()
                    .includes(searchText);
            const matchesCategory =
                category === "All" ||
                event.category === category;
            return matchesSearch &&
                   matchesCategory;
        });
    sortEvents();
}
function sortEvents() {
    const sortValue =
        eventSort.value;
    if (sortValue === "title") {
        currentEvents.sort((a, b) =>
            a.title.localeCompare(b.title)
        );
    }
    if (sortValue === "date") {
        currentEvents.sort((a, b) =>
            a.id - b.id
        );
    }
    currentPage = 1;
    displayEvents();
}
function createPagination() {
    pagination.innerHTML = "";
    const totalPages =
        Math.ceil(
            currentEvents.length /
            eventsPerPage
        );
    if (totalPages <= 1) {
        return;
    }
    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {
        const button =
            document.createElement("button");
        button.innerText = i;
        if (i === currentPage) {
            button.classList.add("active");
        }
        button.addEventListener(
            "click",
            () => {
                currentPage = i;
                displayEvents();
            }
        );
        pagination.appendChild(button);
    }
}
eventSearch.addEventListener(
    "input",
    filterEvents
);
eventCategory.addEventListener(
    "change",
    filterEvents
);
eventSort.addEventListener(
    "change",
    sortEvents
);