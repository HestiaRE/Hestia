// Only masked fields carry the button; the add and edit forms show their passwords in plain text anyway.
export default function handlePasswordReveal() {
	document.querySelectorAll('.js-password-reveal').forEach((button) => {
		const input = button.parentElement.querySelector('input');
		if (!input) {
			return;
		}
		button.addEventListener('click', () => {
			const show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			button.setAttribute('aria-pressed', String(show));
			button.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
			input.focus();
		});
		// Masked again before it is sent: a browser may keep a plain text field in its form history.
		input.form?.addEventListener('submit', () => {
			input.type = 'password';
		});
	});
}
