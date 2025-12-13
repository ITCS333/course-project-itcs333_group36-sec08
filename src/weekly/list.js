/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/
const API_URL = "api/index.php";
// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  const article = document.createElement("article");
  article.className = "card";

  const h2 = document.createElement("h2");
  h2.className = "h5";
  h2.textContent = week.title;

  const startP = document.createElement("p");
  startP.className = "text-muted";
  startP.textContent = `Starts on: ${week.start_date}`;

  const descP = document.createElement("p");
  descP.textContent = week.description || "No description for this week.";

  const link = document.createElement("a");
  link.className = "btn btn-sm";
  link.href = `details.html?id=${encodeURIComponent(week.id)}`;
  link.textContent = "View Details & Discussion";

  article.append(h2, startP, descP, link);
  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  try {
    const res = await fetch(`${API_URL}?resource=weeks`);
    const json = await res.json();

    listSection.innerHTML = "";

    if (!json.success || !Array.isArray(json.data)) {
      const p = document.createElement("p");
      p.className = "text-muted";
      p.textContent = json.error || "Failed to load weeks.";
      listSection.appendChild(p);
      return;
    }

    const weeks = json.data;
    if (!weeks.length) {
      const p = document.createElement("p");
      p.className = "text-muted";
      p.textContent = "No weeks available yet.";
      listSection.appendChild(p);
      return;
    }

    weeks.forEach((week) => {
      listSection.appendChild(createWeekArticle(week));
    });
  } catch (err) {
    console.error("Error loading weeks:", err);
    listSection.innerHTML =
      '<p class="text-muted">Failed to load weeks.</p>';
  }
}
// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
