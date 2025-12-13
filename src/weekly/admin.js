/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
    
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/
const API_URL = "api/index.php";
// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm = document.querySelector('#week-form');

// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTableBody = document.querySelector('#weeks-tbody');

// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
   
  const tr = document.createElement("tr");

  const titleTd = document.createElement("td");
  titleTd.style.padding = "0.45rem 0.2rem";
  titleTd.textContent = week.title;

  const dateTd = document.createElement("td");
  dateTd.style.padding = "0.45rem 0.2rem";
  dateTd.textContent = week.start_date;

  const descTd = document.createElement("td");
  descTd.style.padding = "0.45rem 0.2rem";
  descTd.textContent = week.description || "";

  const actionsTd = document.createElement("td");
  actionsTd.style.padding = "0.45rem 0.2rem";

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "btn btn-sm edit-btn";
  editBtn.dataset.id = week.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "btn btn-sm delete-btn";
  deleteBtn.dataset.id = week.id;

  actionsTd.append(editBtn, deleteBtn);

  tr.append(titleTd, dateTd, descTd, actionsTd);
  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  weeksTableBody.innerHTML = "";

  if (!weeks.length) {
    const tr = document.createElement("tr");
    const td = document.createElement("td");
    td.colSpan = 4;
    td.className = "text-muted";
    td.textContent = "No weeks defined yet. Add your first week above.";
    tr.appendChild(td);
    weeksTableBody.appendChild(tr);
    return;
  }

  weeks.forEach((week) => {
    weeksTableBody.appendChild(createWeekRow(week));
  });
}

async function loadWeeks() {
  try {
    const res = await fetch(`${API_URL}?resource=weeks`);
    const json = await res.json();

    if (json.success && Array.isArray(json.data)) {
      weeks = json.data;
    } else {
      weeks = [];
    }

    renderTable();
  } catch (err) {
    console.error("Error loading weeks:", err);
    weeksTableBody.innerHTML =
      '<tr><td colspan="4" class="text-muted">Failed to load weeks.</td></tr>';
  }
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */
async function handleAddWeek(event) {
  event.preventDefault();

  const titleInput = document.getElementById("week-title");
  const startDateInput = document.getElementById("week-start-date");
  const descriptionInput = document.getElementById("week-description");
  const linksInput = document.getElementById("week-links");

  const title = titleInput.value.trim();
  const startDate = startDateInput.value;
  const description = descriptionInput.value.trim();
  const linksText = linksInput.value;

  if (!title || !startDate) {
    alert("Please provide at least a title and a start date.");
    return;
  }

  const links = linksText
    .split("\n")
    .map((s) => s.trim())
    .filter((s) => s !== "");

  const editingId = weekForm.dataset.editingId;

  try {
    if (editingId) {
      const res = await fetch(
        `${API_URL}?resource=weeks&id=${encodeURIComponent(editingId)}`,
        {
          method: "PUT",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            title,
            start_date: startDate,
            description,
            links,
          }),
        }
      );
      const json = await res.json();
      if (!json.success) {
        alert(json.error || json.message || "Failed to update week.");
        return;
      }
      delete weekForm.dataset.editingId;
      await loadWeeks();
      weekForm.reset();
      alert("Week updated successfully.");
      return;
    }

    const res = await fetch(`${API_URL}?resource=weeks`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title,
        start_date: startDate,
        description,
        links,
      }),
    });

    const json = await res.json();

    if (!json.success) {
      alert(json.error || json.message || "Failed to create week.");
      return;
    }

    await loadWeeks();
    weekForm.reset();
    alert("Week created successfully.");
  } catch (err) {
    console.error("Error creating/updating week:", err);
    alert("Failed to save week. Please try again.");
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
async function handleTableClick(event) {
  const deleteEl = event.target.closest(".delete-btn");
  const editEl = event.target.closest(".edit-btn");

  if (deleteEl) {
    const id = deleteEl.dataset.id;
    if (!id) return;

    if (!confirm("Are you sure you want to delete this week?")) return;

    try {
      const res = await fetch(
        `${API_URL}?resource=weeks&id=${encodeURIComponent(id)}`,
        { method: "DELETE" }
      );
      const json = await res.json();

      if (!json.success) {
        alert(json.error || json.message || "Failed to delete week.");
        return;
      }

      await loadWeeks();
    } catch (err) {
      console.error("Error deleting week:", err);
      alert("Failed to delete week. Please try again.");
    }
    return;
  }

  if (editEl) {
    const id = editEl.dataset.id;
    if (!id) return;

    const week = weeks.find((w) => String(w.id) === String(id));
    if (!week) return;

    document.getElementById("week-title").value = week.title || "";
    document.getElementById("week-start-date").value = week.start_date || "";
    document.getElementById("week-description").value = week.description || "";
    document.getElementById("week-links").value = Array.isArray(week.links)
      ? week.links.join("\n")
      : "";

    weekForm.dataset.editingId = id;
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  await loadWeeks();
  weekForm.addEventListener("submit", handleAddWeek);
  weeksTableBody.addEventListener("click", handleTableClick);
}


// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();

