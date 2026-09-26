import { debounce } from './helpers.js';

// Attach listener to database "Name" and "Username" fields to update their hints
export default function handleDatabaseHints() {
	// All of them: edit db carries a second user field beside the main one.
	const usernameInputs = document.querySelectorAll('.js-db-hint-username');
	const databaseNameInput = document.querySelector('.js-db-hint-database-name');

	if (usernameInputs.length === 0 || !databaseNameInput) {
		return;
	}

	removeUserPrefix(databaseNameInput);
	usernameInputs.forEach(attachUpdateHintListener);
	attachUpdateHintListener(databaseNameInput);
}

// Remove prefix from "Database" input if it exists during initial load (for editing)
function removeUserPrefix(input) {
	const prefixIndex = input.value.indexOf(Alpine.store('globals').USER_PREFIX);
	if (prefixIndex === 0) {
		input.value = input.value.slice(Alpine.store('globals').USER_PREFIX.length);
	}
}

function attachUpdateHintListener(input) {
	if (input.value.trim() !== '') {
		updateHint(input);
	}

	input.addEventListener(
		'input',
		debounce((evt) => updateHint(evt.target)),
	);
}

function updateHint(input) {
	const hintElement = input.parentElement.querySelector('.hint');

	if (input.value.trim() === '') {
		hintElement.textContent = '';
		return;
	}

	hintElement.textContent = Alpine.store('globals').USER_PREFIX + input.value;
}
