(function () {
    "use strict";
	
	const { __, _x } = wp.i18n; // Import translation functions
	
	/* Add role and condition */
	document.addEventListener('DOMContentLoaded', function () {

		// Initialize dynamic behavior for a role
		function initializeRoleLogic(role) {
			const fieldSelect = role.querySelector('.cercf7_selected_field');
			const conditionsList = role.querySelector('.cercf7_conditions_list');
			const addConditionButton = role.querySelector('.cercf7_add_condition');

			// Function to update names dynamically
			function updateConditionNames() {
				const selectedField = fieldSelect.value;
				const conditions = conditionsList.querySelectorAll('li');
				conditions.forEach((condition, index) => {
					const inputs = condition.querySelectorAll('input');
					if (inputs.length === 2) {
						inputs[0].name = `cercf7_${selectedField}_value[${index}]`;
						inputs[1].name = `cercf7_${selectedField}_mail[${index}]`;
					}
				});
			}

			// Add condition dynamically
			addConditionButton.addEventListener('click', function (e) {
				e.preventDefault();

				const selectedField = fieldSelect.value;
				if (!selectedField) {
					alert('Please select a field first.');
					return;
				}

				const conditionIndex = conditionsList.querySelectorAll('li').length;

				const newCondition = document.createElement('li');
				newCondition.innerHTML = `
					<span>${__('Value ==', 'cercf7-pro')}</span> <input type="text" name="cercf7_${selectedField}_value[${conditionIndex}]" value="" placeholder="${__('Enter a value', 'cercf7-pro')}" required> 
					<span>${__('Mail to', 'cercf7-pro')}</span> <input type="text" name="cercf7_${selectedField}_mail[${conditionIndex}]" value="" placeholder="${__('Recipient email', 'cercf7-pro')}" required> <span class="remove_condition" title="${__('Remove Condition', 'cercf7-pro')}">✕</span>
				`;
				conditionsList.appendChild(newCondition);
			});

			// Update names on field change
			fieldSelect.addEventListener('change', function () {
				updateConditionNames();
			});

			// Initialize names for existing conditions
			updateConditionNames();
		}

		// Initialize existing roles
		const roles = document.querySelectorAll('.cercf7-role');
		roles.forEach(role => initializeRoleLogic(role));
	});

	/* Remove a condition */
	document.addEventListener('DOMContentLoaded', function () {
		// Event delegation to handle dynamically added remove condition buttons
		document.body.addEventListener('click', function (e) {
			if (e.target && e.target.classList.contains('remove_condition')) {
				const conditionItem = e.target.closest('li'); // Find the closest <li> element
				if (conditionItem) {
					conditionItem.remove(); // Remove the <li> element
				}
			}
		});
	});
})();