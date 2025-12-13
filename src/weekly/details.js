/*
  Requirement: Populate the weekly detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="week-title"`
     - To the start date <p>: `id="week-start-date"`
     - To the description <p>: `id="week-description"`
     - To the "Exercises & Resources" <ul>: `id="week-links-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Ask a Question" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/
const API_URL = "api/index.php";


// --- Global Data Store ---
// These will hold the data related to *this* specific week.
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.

const weekTitle = document.querySelector("#week-title");
const weekStartDate = document.querySelector("#week-start-date");
const weekDescription = document.querySelector("#week-description");
const weekLinksList = document.querySelector("#week-links-list");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const newCommentText = document.querySelector("#new-comment-text");



// --- Functions ---

/**
 * TODO: Implement the getWeekIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

/**
 * TODO: Implement the renderWeekDetails function.
 * It takes one week object.
 * It should:
 * 1. Set the `textContent` of `weekTitle` to the week's title.
 * 2. Set the `textContent` of `weekStartDate` to "Starts on: " + week's startDate.
 * 3. Set the `textContent` of `weekDescription`.
 * 4. Clear `weekLinksList` and then create and append `<li><a href="...">...</a></li>`
 * for each link in the week's 'links' array. The link's `href` and `textContent`
 * should both be the link URL.
 */

function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = `Starts on: ${week.start_date}`;
  weekDescription.textContent = week.description || "No description for this week.";

  weekLinksList.innerHTML = "";

  const links = Array.isArray(week.links) ? week.links : [];
  if (!links.length) {
    const li = document.createElement("li");
    li.className = "text-muted";
    li.textContent = "No extra links were provided for this week.";
    weekLinksList.appendChild(li);
    return;
  }

  links.forEach((url) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = url;
    a.textContent = url;
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}


/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 * (e.g., an <article> containing a <p> and a <footer>).
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.className = "text-muted";
  footer.textContent = `Posted by: ${comment.author}`;

  article.append(p, footer);
  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  commentList.innerHTML = "";

  if (!currentComments.length) {
    const p = document.createElement("p");
    p.className = "text-muted";
    p.textContent = "No comments yet. Be the first to ask a question.";
    commentList.appendChild(p);
    return;
  }

  currentComments.forEach((c) => {
    commentList.appendChild(createCommentArticle(c));
  });
}

async function loadWeekAndComments() {
  currentWeekId = getWeekIdFromURL();

  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    weekDescription.textContent = "No week id was provided in the URL.";
    return;
  }

  try {
    const [weekRes, commentsRes] = await Promise.all([
      fetch(
        `${API_URL}?resource=weeks&id=${encodeURIComponent(currentWeekId)}`
      ),
      fetch(
        `${API_URL}?resource=comments&week_id=${encodeURIComponent(
          currentWeekId
        )}`
      ),
    ]);

    const weekJson = await weekRes.json();
    const commentsJson = await commentsRes.json();

    if (!weekJson.success || !weekJson.data) {
      weekTitle.textContent = "Week not found.";
      weekDescription.textContent =
        weekJson.error || "The requested week could not be found.";
      return;
    }

    const week = weekJson.data;
    currentComments =
      commentsJson.success && Array.isArray(commentsJson.data)
        ? commentsJson.data
        : [];

    renderWeekDetails(week);
    renderComments();
  } catch (err) {
    console.error("Error loading week details:", err);
    weekTitle.textContent = "Error loading week details.";
    weekDescription.textContent =
      "An error occurred while contacting the server.";
    commentList.innerHTML =
      '<p class="text-muted">Unable to load comments.</p>';
  }
}
/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
async function handleAddComment(event) {
  event.preventDefault();

  const text = newCommentText.value.trim();
  if (!text || !currentWeekId) return;

  try {
    const res = await fetch(`${API_URL}?resource=comments`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        week_id: currentWeekId,
        author: "Student",
        text,
      }),
    });

    const json = await res.json();

    if (!json.success) {
      alert(json.error || "Failed to add comment.");
      return;
    }

    // API returns the saved comment in json.data
    const saved = json.data || {
      author: "Student",
      text,
    };

    currentComments.push({
      author: saved.author,
      text: saved.text,
      created_at: saved.created_at,
    });

    renderComments();
    newCommentText.value = "";
  } catch (err) {
    console.error("Error creating comment:", err);
    alert("Failed to create comment. Please try again.");
  }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentWeekId` by calling `getWeekIdFromURL()`.
 * 2. If no ID is found, set `weekTitle.textContent = "Week not found."` and stop.
 * 3. `fetch` both 'weeks.json' and 'week-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct week from the weeks array using the `currentWeekId`.
 * 6. Get the correct comments array from the comments object using the `currentWeekId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the week is found:
 * - Call `renderWeekDetails()` with the week object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the week is not found, display an error in `weekTitle`.
 */
 
  
 async function initializePage() {
  // ... your implementation here ...
  await loadWeekAndComments();
  commentForm.addEventListener("submit", handleAddComment);
}
   
// --- Initial Page Load ---
initializePage();
