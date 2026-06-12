/*
	import useDataTableStyles from "../hooks/useDataTableStyles";
	const customStyles = useDataTableStyles();
	<DataTable
		columns={columns}
		data={data}
		customStyles={customStyles}
	/>
*/
import { useMemo } from "react";
export default function useDataTableStyles() {
    return useMemo(() => {
        return {
            table: {
                style: {
                    fontSize: "13px",
                    overflow: "visible",
                    borderRadius: "0 0 16px 16px",
                },
            },
            headRow: {
                style: {
                    backgroundColor: "#fafbfc",
                    minHeight: "44px",
                    borderBottomColor: "#eef2f7",
                    borderBottomWidth: "1.5px",
                    borderBottomStyle: "solid",
                },
            },
            headCells: {
                style: {
                    fontSize: "11px",
                    fontWeight: "700",
                    color: "#9ca3af",
                    letterSpacing: "0.7px",
                    textTransform: "uppercase",
                    padding: "0 16px",
                },
            },
            rows: {
                style: {
                    minHeight: "56px",
                    borderBottomColor: "#f3f4f6",
                    fontSize: "13px",
                    overflow: "visible",
                    transition: "background 0.12s",
                },
                highlightOnHoverStyle: {
                    backgroundColor: "#fff7ed",
                    borderBottomColor: "#f1d9c4",
                    outlineColor: "#fed7aa",
                    transition: "background 0.12s",
                },
            },
            cells: {
                style: {
                    fontSize: "13px",
                    padding: "0 16px",
                    display: "flex",
                    alignItems: "center",
                    overflow: "visible",
                },
            },
            pagination: {
                style: {
                    borderTop: "1px solid #f1f5f9",
                    backgroundColor: "#fff",
                    minHeight: "52px",
                    fontSize: "12px",
                    color: "#6b7280",
                    fontWeight: "500",
                    borderRadius: "0 0 16px 16px",
                },
                pageButtonsStyle: {
                    borderRadius: "8px",
                    height: "30px",
                    width: "30px",
                    padding: "4px",
                    margin: "0 2px",
                    cursor: "pointer",
                    transition: "all 0.15s",
                    fill: "#9ca3af",
                    "&:disabled": {
                        fill: "#d1d5db",
                        cursor: "default",
                    },
                    "&:hover:not(:disabled)": {
                        backgroundColor: "#fff7ed",
                        fill: "#f97316",
                    },
                    "&:focus": {
                        outline: "none",
                        backgroundColor: "#fff7ed",
                    },
                },
            },
        };
    }, []);
}
