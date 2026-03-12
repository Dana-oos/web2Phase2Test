/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/ClientSide/javascript.js to edit this template
 */


const ingredientsList = document.getElementById("ingredientsList");
const instructionsList = document.getElementById("instructionsList");


function addIngredient(value = "") {
const div = document.createElement("div");
div.className = "dynamic-group";
div.innerHTML = `<input type="text" required value="${value}"><button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>`;
ingredientsList.appendChild(div);
}


function addInstruction(value = "") {
const div = document.createElement("div");
div.className = "dynamic-group";
div.innerHTML = `<textarea required>${value}</textarea><button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>`;
instructionsList.appendChild(div);
}


if (ingredientsList && instructionsList) {
addIngredient();
addInstruction();
}


document.getElementById("recipeForm")?.addEventListener("submit", e => {
e.preventDefault();
window.location.href = "my-recipes.html";
});


// ===== Prefill data for EDIT page (frontend simulation) =====
if (document.body.classList.contains("edit-page")) {

  const existingIngredients = [
    "1 ripe banana",
    "1 cup oats, rolled or quick oats, or oat flour",
    "1 cup non-dairy milk"
  ];

  const existingInstructions = [
    "Add the banana, oats, and non-dairy milk to a blender. Blend until smooth. If you're using add-ins (like cinnamon, chocolate chips, blueberries) add them in now.",
    "Heat a large non-stick pan over medium-low heat. Once the pan is hot, pour the batter into small pancakes (about 1/4 cup each). Cook for 2–3 minutes, or until bubbles form on the surface and the edges start to set.",
    "Flip the pancakes and cook for another 1–2 minutes, until golden brown. Serve warm with your favorite toppings. Enjoy!"
  ];

  // Clear default empty fields
  ingredientsList.innerHTML = "";
  instructionsList.innerHTML = "";

  // Insert existing data
  existingIngredients.forEach(item => addIngredient(item));
  existingInstructions.forEach(step => addInstruction(step));
}