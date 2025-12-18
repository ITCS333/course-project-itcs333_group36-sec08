// ===============================
// API CONFIG
// ===============================
const API_URL = '/src/discussion/api/index.php';

// ===============================
// GLOBAL STATE
// ===============================
let currentTopicId = null;
let currentReplies = [];

// ===============================
// ELEMENTS
// ===============================
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

// ===============================
// HELPERS
// ===============================
function getTopicIdFromURL() {
  return new URLSearchParams(window.location.search).get('id');
}

// ===============================
// RENDER ORIGINAL POST
// ===============================
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by ${topic.author} on ${topic.created_at}`;
}

// ===============================
// CREATE REPLY ARTICLE
// ===============================
function createReplyArticle(reply) {
  const article = document.createElement('article');
  article.className = 'reply';

  article.innerHTML = `
    <p>${reply.text}</p>
    <footer>
      <span>By ${reply.author}</span>
      <span>${reply.created_at}</span>
    </footer>
    <button 
      class="delete-reply-btn" 
      data-id="${reply.reply_id}">
      Delete
    </button>
  `;

  return article;
}

// ===============================
// RENDER REPLIES
// ===============================
function renderReplies() {
  replyListContainer.innerHTML = '';
  currentReplies.forEach(reply => {
    replyListContainer.appendChild(createReplyArticle(reply));
  });
}

// ===============================
// ADD REPLY (POST)
// ===============================
async function handleAddReply(event) {
  event.preventDefault();

  const text = newReplyText.value.trim();
  if (!text) return;

  const newReply = {
    reply_id: `reply_${Date.now()}`,
    id: currentTopicId,
    text,
    author: 'Student'
  };

  const response = await fetch(`${API_URL}?resource=replies`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(newReply)
  });

  const result = await response.json();
  if (!result.success) return alert(result.message);

  // reload replies from server
  await loadReplies();
  newReplyText.value = '';
}

// ===============================
// DELETE REPLY
// ===============================
async function handleReplyListClick(event) {
  if (!event.target.classList.contains('delete-reply-btn')) return;

  const replyId = event.target.dataset.id;

  const response = await fetch(
    `${API_URL}?resource=replies&id=${replyId}`,
    { method: 'DELETE' }
  );

  const result = await response.json();
  if (!result.success) return alert(result.message);

  currentReplies = currentReplies.filter(r => r.reply_id !== replyId);
  renderReplies();
}

// ===============================
// LOAD REPLIES
// ===============================
async function loadReplies() {
  const response = await fetch(
    `${API_URL}?resource=replies&id=${currentTopicId}`
  );
  const result = await response.json();

  if (!result.success) throw new Error(result.message);
  currentReplies = result.data;
  renderReplies();
}

// ===============================
// INITIALIZE PAGE
// ===============================
async function initializePage() {
  currentTopicId = getTopicIdFromURL();

  if (!currentTopicId) {
    topicSubject.textContent = 'Topic not found.';
    return;
  }

  try {
    // 1️⃣ load topic
    const topicResponse = await fetch(
      `${API_URL}?resource=topics&id=${currentTopicId}`
    );
    const topicResult = await topicResponse.json();

    if (!topicResult.success) {
      topicSubject.textContent = 'Topic not found.';
      return;
    }

    renderOriginalPost(topicResult.data);

    // 2️⃣ load replies
    await loadReplies();

    // listeners
    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);

  } catch (err) {
    console.error(err);
    topicSubject.textContent = 'Error loading topic.';
  }
}

// ===============================
// START
// ===============================
initializePage();
