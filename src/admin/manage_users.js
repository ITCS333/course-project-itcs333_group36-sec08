
/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const studentTableBody = document.querySelector('#student-table tbody');

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.getElementById('add-student-form');

// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const passwordForm = document.getElementById('password-form');

// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.getElementById('search-input');

// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll('#student-table thead th');

// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  const row = document.createElement('tr');
  
  // Create table cells for student data
  const nameCell = document.createElement('td');
  nameCell.textContent = student.name;
  
  const idCell = document.createElement('td');
  idCell.textContent = student.id;
  
  const emailCell = document.createElement('td');
  emailCell.textContent = student.email;
  
  // Create actions cell with buttons
  const actionsCell = document.createElement('td');
  actionsCell.className = 'action-buttons';
  
  const editButton = document.createElement('button');
  editButton.textContent = 'Edit';
  editButton.className = 'edit-btn btn-edit';
  editButton.setAttribute('data-id', student.id);
  
  const deleteButton = document.createElement('button');
  deleteButton.textContent = 'Delete';
  deleteButton.className = 'delete-btn btn-delete';
  deleteButton.setAttribute('data-id', student.id);
  
  actionsCell.appendChild(editButton);
  actionsCell.appendChild(deleteButton);
  
  // Append all cells to the row
  row.appendChild(nameCell);
  row.appendChild(idCell);
  row.appendChild(emailCell);
  row.appendChild(actionsCell);
  
  return row;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // Clear the table body
  studentTableBody.innerHTML = '';
  
  // Loop through students and create rows
  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
  event.preventDefault();
  
  const currentPassword = document.getElementById('current-password').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  
  // Validation
  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }
  
  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }
  
  // If validation passes
  alert('Password updated successfully!');
  
  // Clear the form
  passwordForm.reset();
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  event.preventDefault();
  
  const name = document.getElementById('student-name').value.trim();
  const id = document.getElementById('student-id').value.trim();
  const email = document.getElementById('student-email').value.trim();
  
  // Validation
  if (!name || !id || !email) {
    alert('Please fill out all required fields.');
    return;
  }
  
  // Check if student ID already exists
  const existingStudent = students.find(student => student.id === id);
  if (existingStudent) {
    alert(`A student with ID ${id} already exists.`);
    return;
  }
  
  // Create and add new student
  const newStudent = {
    name: name,
    id: id,
    email: email
  };
  
  students.push(newStudent);
  renderTable(students);
  
  // Clear the form
  addStudentForm.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
  const target = event.target;
  
  // Handle delete button click
  if (target.classList.contains('delete-btn')) {
    const studentId = target.getAttribute('data-id');
    
    if (confirm('Are you sure you want to delete this student?')) {
      // Filter out the student with the matching ID
      students = students.filter(student => student.id !== studentId);
      renderTable(students);
    }
  }
  
  // Handle edit button click (optional)
  if (target.classList.contains('edit-btn')) {
    const studentId = target.getAttribute('data-id');
    const student = students.find(s => s.id === studentId);
    
    if (student) {
      // For now, just show an alert. In a real app, you'd show an edit form.
      alert(`Editing student: ${student.name}\nID: ${student.id}\nEmail: ${student.email}`);
      
      // Example of how you might implement editing:
      // const newName = prompt('Enter new name:', student.name);
      // if (newName) {
      //   student.name = newName;
      //   renderTable(students);
      // }
    }
  }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  const searchTerm = searchInput.value.toLowerCase().trim();
  
  if (searchTerm === '') {
    renderTable(students);
  } else {
    const filteredStudents = students.filter(student => 
      student.name.toLowerCase().includes(searchTerm)
    );
    renderTable(filteredStudents);
  }
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  const th = event.currentTarget;
  const columnIndex = th.cellIndex;
  
  // Determine which property to sort by based on column index
  let sortProperty;
  switch (columnIndex) {
    case 0: sortProperty = 'name'; break;
    case 1: sortProperty = 'id'; break;
    case 2: sortProperty = 'email'; break;
    default: return; // Don't sort actions column
  }
  
  // Get current sort direction or default to 'asc'
  let sortDirection = th.getAttribute('data-sort-dir') || 'asc';
  
  // Toggle sort direction
  sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
  th.setAttribute('data-sort-dir', sortDirection);
  
  // Reset sort direction on other headers
  tableHeaders.forEach(header => {
    if (header !== th) {
      header.removeAttribute('data-sort-dir');
    }
  });
  
  // Sort the students array
  students.sort((a, b) => {
    let result;
    
    if (sortProperty === 'id') {
      // Compare as numbers for ID
      result = parseInt(a[sortProperty]) - parseInt(b[sortProperty]);
    } else {
      // Use localeCompare for strings (name and email)
      result = a[sortProperty].localeCompare(b[sortProperty]);
    }
    
    // Reverse result for descending order
    return sortDirection === 'desc' ? -result : result;
  });
  
  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  try {
    // Fetch student data
    const response = await fetch('students.json');
    
    if (!response.ok) {
      throw new Error(`Failed to load student data: ${response.status}`);
    }
    
    const studentData = await response.json();
    students = studentData;
    
    // Render the table with the loaded data
    renderTable(students);
    
    // Set up event listeners
    passwordForm.addEventListener('submit', handleChangePassword);
    addStudentForm.addEventListener('submit', handleAddStudent);
    studentTableBody.addEventListener('click', handleTableClick);
    searchInput.addEventListener('input', handleSearch);
    
    tableHeaders.forEach(header => {
      header.addEventListener('click', handleSort);
    });
    
    console.log('Admin portal initialized successfully');
    
  } catch (error) {
    console.error('Error initializing admin portal:', error);
    // Fallback: Use hardcoded data if fetch fails
    students = [
      {
        "name": "Ali Hassan",
        "id": "202101234",
        "email": "202101234@stu.uob.edu.bh"
      },
      {
        "name": "Fatema Ahmed",
        "id": "202205678",
        "email": "202205678@stu.uob.edu.bh"
      },
      {
        "name": "Mohamed Abdulla",
        "id": "202311001",
        "email": "202311001@stu.uob.edu.bh"
      },
      {
        "name": "Noora Salman",
        "id": "202100987",
        "email": "202100987@stu.uob.edu.bh"
      },
      {
        "name": "Zainab Ebrahim",
        "id": "202207766",
        "email": "202207766@stu.uob.edu.bh"
      }
    ];
    renderTable(students);
    
    // Still set up event listeners even if fetch failed
    passwordForm.addEventListener('submit', handleChangePassword);
    addStudentForm.addEventListener('submit', handleAddStudent);
    studentTableBody.addEventListener('click', handleTableClick);
    searchInput.addEventListener('input', handleSearch);
    
    tableHeaders.forEach(header => {
      header.addEventListener('click', handleSort);
    });
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
