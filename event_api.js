
const apiURL = "api/events.php";

const eventForm = document.getElementById("eventForm");
const eventTable = document.getElementById("eventTable");
const messageBox = document.getElementById("message");
const loading = document.getElementById("loading");

let events = [];

function showMessage(message, isError = false) {
    messageBox.textContent = message;
    messageBox.className = isError ? "error" : "message";
}

function setLoading(value) {
    loading.hidden = !value;
}

async function requestAPI(method, data = null, search = "") {
    let url = apiURL;

    if (search !== "") {
        url += "?search=" + encodeURIComponent(search);
    }

    const options = { method };

    if (data !== null) {
        options.headers = {
            "Content-Type": "application/json"
        };
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Something went wrong.");
    }

    return result;
}

async function loadEvents(search = "") {
    setLoading(true);
    eventTable.innerHTML = "<tr><td colspan='7'>Loading...</td></tr>";

    try {
        const result = await requestAPI("GET", null, search);
        events = result.data || [];

        eventTable.innerHTML = "";

        if (events.length === 0) {
            eventTable.innerHTML =
                "<tr><td colspan='7'>No events found.</td></tr>";
            return;
        }

        events.forEach(event => {
            const row = document.createElement("tr");

            const values = [
                event.event_id,
                event.title,
                event.event_date,
                event.venue,
                event.category,
                event.status
            ];

            values.forEach(value => {
                const cell = document.createElement("td");
                cell.textContent = value ?? "";
                row.appendChild(cell);
            });

            const actions = document.createElement("td");

            const editButton = document.createElement("button");
            editButton.textContent = "Edit";
            editButton.type = "button";
            editButton.addEventListener("click", () => editEvent(event));

            const deleteButton = document.createElement("button");
            deleteButton.textContent = "Delete";
            deleteButton.type = "button";
            deleteButton.className = "delete";
            deleteButton.style.marginLeft = "5px";
            deleteButton.addEventListener("click", () => deleteEvent(event));

            actions.append(editButton, deleteButton);
            row.appendChild(actions);
            eventTable.appendChild(row);
        });
    } catch (error) {
        eventTable.innerHTML =
            "<tr><td colspan='7'>Could not load events.</td></tr>";
        showMessage(error.message, true);
    } finally {
        setLoading(false);
    }
}

function editEvent(event) {
    document.getElementById("event_id").value = event.event_id;
    document.getElementById("title").value = event.title;
    document.getElementById("event_date").value = event.event_date;
    document.getElementById("venue").value = event.venue;
    document.getElementById("category").value = event.category || "General";
    document.getElementById("status").value = event.status || "Open";

    // The list API does not return descriptions, so clear this field.
    document.getElementById("description").value = "";

    document.getElementById("formTitle").textContent = "Edit Event";
    document.getElementById("saveButton").textContent = "Update Event";
    document.getElementById("cancelButton").hidden = false;

    window.scrollTo({ top: 0, behavior: "smooth" });
}

function resetForm() {
    eventForm.reset();
    document.getElementById("event_id").value = "";
    document.getElementById("category").value = "General";
    document.getElementById("formTitle").textContent = "Add Event";
    document.getElementById("saveButton").textContent = "Add Event";
    document.getElementById("cancelButton").hidden = true;
}

eventForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const id = document.getElementById("event_id").value;

    const data = {
        action: id ? "update" : "add",
        title: document.getElementById("title").value.trim(),
        event_date: document.getElementById("event_date").value,
        venue: document.getElementById("venue").value.trim(),
        category: document.getElementById("category").value.trim(),
        status: document.getElementById("status").value,
        description: document.getElementById("description").value.trim()
    };

    if (id) {
        data.event_id = Number(id);
    }

    setLoading(true);

    try {
        const result = await requestAPI("POST", data);
        showMessage(result.message);
        resetForm();
        await loadEvents(document.getElementById("search").value.trim());
    } catch (error) {
        showMessage(error.message, true);
    } finally {
        setLoading(false);
    }
});

async function deleteEvent(event) {
    if (!confirm("Delete event: " + event.title + "?")) {
        return;
    }

    setLoading(true);

    try {
        const result = await requestAPI("POST", {
            action: "delete",
            event_id: Number(event.event_id)
        });

        showMessage(result.message);
        await loadEvents(document.getElementById("search").value.trim());
    } catch (error) {
        showMessage(error.message, true);
    } finally {
        setLoading(false);
    }
}

document.getElementById("cancelButton").addEventListener("click", resetForm);

document.getElementById("searchButton").addEventListener("click", () => {
    loadEvents(document.getElementById("search").value.trim());
});

document.getElementById("resetButton").addEventListener("click", () => {
    document.getElementById("search").value = "";
    loadEvents();
});

document.getElementById("search").addEventListener("keydown", event => {
    if (event.key === "Enter") {
        event.preventDefault();
        loadEvents(event.target.value.trim());
    }
});

loadEvents();
