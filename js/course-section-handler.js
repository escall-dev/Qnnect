// JavaScript to handle dynamic section loading based on course selection
function updateSections() {
    // Get the selected course
    const courseSelect = document.getElementById('studentCourse');
    const sectionSelect = document.getElementById('studentSection');
    const courseValue = courseSelect.value;
    
    // Clear current section options
    sectionSelect.innerHTML = '';
    
    // If no course is selected, show default option
    if (!courseValue) {
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = 'Select a course first';
        sectionSelect.appendChild(defaultOption);
        return;
    }
    
    // Get all sections from our PHP-provided data
    const sections = <?php echo json_encode($sections); ?>;
    
    // Find sections associated with the selected course
    const filteredSections = sections.filter(section => section.course_name === courseValue);
    
    // If no sections found for this course
    if (filteredSections.length === 0) {
        const noOption = document.createElement('option');
        noOption.value = '';
        noOption.disabled = true;
        noOption.selected = true;
        noOption.textContent = 'No sections available for this course';
        sectionSelect.appendChild(noOption);
    } else {
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = 'Select Section';
        sectionSelect.appendChild(defaultOption);
        
        // Add sections to dropdown
        filteredSections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.section_name;
            option.textContent = section.section_name;
            sectionSelect.appendChild(option);
        });
    }
    
    // Update the hidden combined field
    updateCombinedField();
}

// Update the hidden field with combined course-section value
function updateCombinedField() {
    const courseSelect = document.getElementById('studentCourse');
    const sectionSelect = document.getElementById('studentSection');
    const combinedField = document.getElementById('courseSectionCombined');
    
    if (courseSelect.value && sectionSelect.value) {
        combinedField.value = courseSelect.value + '-' + sectionSelect.value;
    } else {
        combinedField.value = '';
    }
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('studentCourse');
    const sectionSelect = document.getElementById('studentSection');
    
    courseSelect.addEventListener('change', updateSections);
    sectionSelect.addEventListener('change', updateCombinedField);
    
    // Initialize the sections dropdown
    updateSections();
});
