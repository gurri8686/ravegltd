// src/components/ServerTable.jsx
import React from "react";
import DataTable from "react-data-table-component";

export default function ServerTable({
    title,
    columns,
    tableHook
}) {
    const {
        data,
        totalRows,
        loading,
        search,
        setSearch,
        handlePageChange,
        handlePerRowsChange,
        handleSort,
    } = tableHook;

    return (
        <div className="card">
            <div className="card-header pb-0 d-flex justify-content-between align-items-center">
                <h5 className="card-title mb-0">{title}</h5>
                <input
                    type="text"
                    className="form-control w-25"
                    placeholder="Search..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>
            <div className="card-body">
                <DataTable
                    columns={columns}
                    data={data}
                    progressPending={loading}
                    pagination
                    paginationServer
                    paginationTotalRows={totalRows}
                    onChangeRowsPerPage={handlePerRowsChange}
                    onChangePage={handlePageChange}
                    onSort={handleSort}
                    sortServer
                    highlightOnHover
                    striped
                    responsive
                />
            </div>
        </div>
    );
}
