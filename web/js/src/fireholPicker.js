// FireHOL page (#510): a ticked combined list hides and unticks the lists it contains, and the sum follows the ticks.
export default function handleFireholPicker() {
	const rows = [...document.querySelectorAll('.js-firehol-row')];
	if (!rows.length) {
		return;
	}
	const sum = document.querySelector('.js-firehol-sum');
	const small = document.querySelector('.js-firehol-small');
	const showSmall = document.querySelector('.js-firehol-show-small');
	const box = (row) => row.querySelector('.js-firehol-list');

	function update() {
		const covered = new Set();
		rows.forEach((row) => {
			if (box(row).checked) {
				row.dataset.includes.split(' ').filter(Boolean).forEach((name) => covered.add(name));
			}
		});
		let total = 0;
		rows.forEach((row) => {
			const hidden = covered.has(row.dataset.name);
			row.classList.toggle('u-hidden', hidden);
			if (hidden) {
				box(row).checked = false;
			}
			if (box(row).checked) {
				total += Number(row.dataset.entries) || 0;
			}
		});
		if (sum) {
			const over = total > Number(sum.dataset.limit);
			sum.textContent = sum.dataset.text.replace('%d', total) + (over ? ` ${sum.dataset.warn}` : '');
			sum.classList.toggle('icon-red', over);
		}
	}

	rows.forEach((row) => box(row).addEventListener('change', update));

	if (showSmall && small) {
		// A saved set may already hold a small list, which must not sit ticked out of sight.
		if (small.querySelector('.js-firehol-list:checked')) {
			small.classList.remove('u-hidden');
			showSmall.classList.add('u-hidden');
		}
		showSmall.addEventListener('click', () => {
			small.classList.remove('u-hidden');
			showSmall.classList.add('u-hidden');
		});
	}

	update();
}
