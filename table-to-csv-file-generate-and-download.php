function tableToCSV() {
    var csv_data = [];
    var table = document.querySelector("table"); // Assuming there's one table
    var rows = table.rows;
    var mergeTracker = []; // Tracks row-spanned cells

    for (var i = 0; i < rows.length; i++) {
        
        if (isHidden(rows[i])) continue; // Skip hidden rows

        var cols = rows[i].cells;
        var csvrow = [];
        var colIndex = 0;

        for (var j = 0; j < cols.length; j++) {

            if (isHidden(cols[j])) continue; // Skip hidden cells

            while (mergeTracker[colIndex]) {
                // Insert empty values for cells spanning multiple rows
                csvrow.push('""');
                mergeTracker[colIndex]--;
                colIndex++;
            }

            var cell = cols[j];
            var cellValue = cell.innerText.trim().replace(/"/g, '""'); // Escape double quotes
            csvrow.push(`"${cellValue}"`);

            if (cell.rowSpan > 1) {
                mergeTracker[colIndex] = cell.rowSpan - 1; // Store rowspan info
            }

            if (cell.colSpan > 1) {
                for (var k = 1; k < cell.colSpan; k++) {
                    csvrow.push('""'); // Add empty values for merged columns
                }
                colIndex += cell.colSpan;
            } else {
                colIndex++;
            }
        }

        csv_data.push(csvrow.join(","));
    }

    // Convert array to string and download
    csv_data = csv_data.join("\n");
    downloadCSVFile(csv_data);
}

function isHidden(el) {
    return (
        el.classList.contains("hidden") || 
        getComputedStyle(el).display === "none"
    );
}

function downloadCSVFile(csv_data) {
    var csvFile = new Blob([csv_data], { type: "text/csv" });
    var tempLink = document.createElement("a");
    tempLink.href = URL.createObjectURL(csvFile);
    tempLink.download = "csv-file-name.csv";
    tempLink.style.display = "none";
    document.body.appendChild(tempLink);
    tempLink.click();
    document.body.removeChild(tempLink);
}
