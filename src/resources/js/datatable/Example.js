// src/components/PaymentsTable.jsx
import React from "react";
import useServerTable from "../hooks/useServerTable";
import ServerTable from "./ServerTable";

export default function PaymentsTable() {
    const tableHook = useServerTable("/api/payments");

    const columns = [
        { name: "ID", selector: (row) => row.id, sortable: true },
        { name: "Name", selector: (row) => row.name, sortable: true },
        { name: "Email", selector: (row) => row.email },
        { name: "Amount", selector: (row) => row.amount, right: true },
        { name: "Date", selector: (row) => row.date },
    ];

    return <ServerTable title="Payments" columns={columns} tableHook={tableHook} />;
}
