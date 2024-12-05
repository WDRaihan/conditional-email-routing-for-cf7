document.addEventListener('DOMContentLoaded', function () {
    const addConditionButton = document.getElementById('cercf7_add_condition');
    const conditionsList = document.getElementById('cercf7_conditions_list');
    const fieldSelect = document.getElementById('cercf7_selected_field');

    // Function to update all existing condition names based on selected field
    function updateConditionNames() {
        const selectedField = fieldSelect.value;
        if (!selectedField) {
            return;
        }

        // Update the name attributes of all inputs
        const conditions = conditionsList.querySelectorAll('li');
        conditions.forEach((condition, index) => {
            const inputs = condition.querySelectorAll('input');
            if (inputs.length === 2) {
                inputs[0].name = `cercf7_${selectedField}_value[${index}]`;
                inputs[1].name = `cercf7_${selectedField}_mail[${index}]`;
            }
        });
    }

    // Event listener for adding a new condition
    addConditionButton.addEventListener('click', function (e) {
        e.preventDefault();

        const selectedField = fieldSelect.value;
        if (!selectedField) {
            alert('Please select a field first.');
            return;
        }

        // Get the current number of conditions for this field
        const conditionIndex = conditionsList.querySelectorAll('li').length;

        // Create a new list item
        const newCondition = document.createElement('li');
        newCondition.innerHTML = `
            Value == <input type="text" name="cercf7_${selectedField}_value[${conditionIndex}]" value=""> 
            Mail to <input type="text" name="cercf7_${selectedField}_mail[${conditionIndex}]" value="">
        `;

        // Append the new condition to the list
        conditionsList.appendChild(newCondition);
    });

    // Event listener for changing the selected field
    fieldSelect.addEventListener('change', function () {
        updateConditionNames();
    });
});