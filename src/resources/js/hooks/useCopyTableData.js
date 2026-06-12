/**
 * const { handleCopy, copied } = useCopyTableData(columns, filteredData);
 */
import { useState } from "react";

export default function useCopyTableData(columns, data) {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        try {
            // Extract Header Row
            const header = columns
                .map(col => typeof col.name === "string" ? col.name : "")
                .join("\t");

            // Extract Visible Rows
            const rows = data.map((row, index) => {
                return columns.map(col => {
                    if (col.selector) {
                        return col.selector(row, index) ?? "";
                    }
                    return row[col.selector] ?? "";
                }).join("\t");
            });

            const finalText = [header, ...rows].join("\n");

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(finalText).then(() => {
                    setCopied(true);
                    setTimeout(() => setCopied(false), 1500);
                });
            } else {
                const textarea = document.createElement("textarea");
                textarea.value = finalText;
                textarea.style.position = "fixed";
                textarea.style.opacity = "0";
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                setCopied(true);
                setTimeout(() => setCopied(false), 1500);
            }

        } catch (error) {
            console.error("Copy failed:", error);
        }
    };

    return { handleCopy, copied };
}
