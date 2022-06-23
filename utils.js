const table = document.querySelector("#table-document-delivery");
const tableBulkActions = document.querySelector("#table-document-bulk-actions");
const allTableHeaders = document.querySelectorAll(
  "#table-document-delivery th"
);
const allTableHeadersBulk = document.querySelectorAll(
  "#table-document-bulk-actions th"
);

/**
 * Sorting Table via Table headers
 * @params {HTMLElementTable} Table to get sorted
 * @params {number} column index to sort
 * @params {boolean} asc Determines if sorting will be in ascending
 *
 */

const sortTableByColumn = (table, column, asc = true) => {
  const dirModifier = asc ? 1 : -1;
  const tbody = table.tBodies[0];
  const rows = Array.from(tbody.querySelectorAll("tr"));

  // Sort each row
  const sortedRows = rows.sort((ele1, ele2) => {
    const ele1ColText = ele1
      .querySelector(`td:nth-child(${column + 1})`)
      .textContent.trim();
    const ele2ColText = ele2
      .querySelector(`td:nth-child(${column + 1})`)
      .textContent.trim();
    return ele1ColText > ele2ColText ? 1 * dirModifier : -1 * dirModifier;
  });

  // Remove all existing tr from table

  while (tbody.firstChild) {
    tbody.removeChild(tbody.firstChild);
  }

  // Re Add new sorted rows into the table
  tbody.append(...sortedRows);

  // How the Column is currently sorted
  table.querySelectorAll("th").forEach((th) => {
    th.classList.remove("th-sort-asc", "th-sort-desc");
  });

  table
    .querySelector(`th:nth-child(${column + 1})`)
    .classList.toggle("th-sort-asc", asc);

  table
    .querySelector(`th:nth-child(${column + 1})`)
    .classList.toggle("th-sort-desc", !asc);
};
const inputchk = document.querySelector('input[type= "checkbox"]');

allTableHeaders.forEach((th, index) => {
  if (th.children[index] !== inputchk) {
    th.addEventListener("click", () => {
      const currentSorting = th.classList.contains("th-sort-asc");
      sortTableByColumn(table, index, !currentSorting);
    });
  }
});
allTableHeadersBulk.forEach((th, index) => {
  if (th.children[index] !== inputchk) {
    th.addEventListener("click", () => {
      const currentSorting = th.classList.contains("th-sort-asc");
      sortTableByColumn(tableBulkActions, index, !currentSorting);
    });
  }
});

// Check all users if the main checkbox is selected

// This gives a nodelist o fall the checkboxes to check
const allCheckboxes = document.querySelectorAll(
  '#table-document-delivery tbody input[type="checkbox"]'
);

// This selects the checkbox to select all the other checkbox in hsirt the checkbox in the th
const selectAllCheckbox = document.querySelector(
  '#table-document-delivery thead input[type="checkbox"]'
);

// This gives a nodelist o fall the checkboxes to check
const allCheckboxesBulk = document.querySelectorAll(
  '#table-document-bulk-actions tbody input[type="checkbox"]'
);

// This selects the checkbox to select all the other checkbox in hsirt the checkbox in the th
const selectAllCheckboxBulk = document.querySelector(
  '#table-document-bulk-actions thead input[type="checkbox"]'
);

/**
 *
 * @param  thisCheckbox is the checkbox which allows user to select all the other checkboxes
 * @param  allCheckboxesToCheck it is the nodelist of all the other checkboxes to check
 */
const checkAll = (thisCheckbox, allCheckboxesToCheck) => {
  if (thisCheckbox.checked == true) {
    allCheckboxesToCheck.forEach((checkbox) => {
      checkbox.checked = true;
    });
  } else {
    allCheckboxesToCheck.forEach((checkbox) => {
      checkbox.checked = false;
    });
  }
};

if (selectAllCheckbox) {
  selectAllCheckbox.addEventListener("click", () => {
    checkAll(selectAllCheckbox, allCheckboxes);
  });
}

if (selectAllCheckboxBulk) {
  selectAllCheckboxBulk.addEventListener("click", () => {
    alert("test");
    checkAll(selectAllCheckboxBulk, allCheckboxesBulk);
  });
}

// // copy all function
// $("table tbody td:first-child").on("click", (e) => {
//   const copyvalue = e.target.innerHTML;
//   navigator.clipboard.writeText(copyvalue);
// });

const copyToclipboard = (e) => {
  const textToCopy = e.target.innerHTML;
  console.log(textToCopy);
};

const style = document.createElement("style");
document.head.appendChild(style);
style.sheet.insertRule(`
.icon_container {
  background: #4BB543;
  border-radius: 5px;
  padding:  2px 5px;
  position: relative;
}

`);
// style.sheet.insertRule(`
// .icon_container::after {
//   content: ${"\25be"};
// }
// `);
const allChampsFusion = document.querySelectorAll(
  "#champs_table_container table tbody td:first-child"
);
allChampsFusion.forEach((champsFusion) => {
  champsFusion.style.cursor = "pointer";
  champsFusion.addEventListener("click", (e) => {
    const textTocopy = champsFusion.innerHTML;
    const iconContainer = document.createElement("span");
    iconContainer.append("Copied");
    iconContainer.classList.add("icon_container");

    champsFusion.appendChild(iconContainer);

    iconContainer.style.color = "#fff";

    setTimeout(() => {
      champsFusion.removeChild(iconContainer);
    }, 3000);
    navigator.clipboard.writeText(textTocopy);
  });
});

// const cssLinkFile = '<link rel="stylesheet" href="./styles/style.css">';
// $("head").append(cssLinkFile);

$(".test_h1").append('<i class="fas fa-sort-up"></i>');
$("table th").css({
  position: "relative",
  cursor: "pointer",
  fontWeight: "600",
});
