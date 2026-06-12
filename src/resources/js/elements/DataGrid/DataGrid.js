// components/DataGrid/DataGrid.jsx
import React, { useEffect, useState } from "react";
import axios from "axios";
import "./DataGrid.css";

const DataGrid = ({
  apiUrl,
  columns,
  params = {}, // any extra params (filters, etc.)
  pageSizeOptions = [10, 25, 50, 100],
}) => {
  const [data, setData] = useState([]);
  const [search, setSearch] = useState("");
  const [sortField, setSortField] = useState("");
  const [sortOrder, setSortOrder] = useState("asc");
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(pageSizeOptions[0]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);

  const fetchData = async () => {
    setLoading(true);
    try {
      const response = await axios.get(apiUrl, {
        params: {
          page,
          limit,
          search,
          sortField,
          sortOrder,
          ...params,
        },
      });
      setData(response.data.data || []);
      setTotal(response.data.total || 0);
    } catch (error) {
      console.error("Error fetching data", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [page, limit, search, sortField, sortOrder]);

  const handleSort = (field) => {
    const order = sortField === field && sortOrder === "asc" ? "desc" : "asc";
    setSortField(field);
    setSortOrder(order);
  };

  const totalPages = Math.ceil(total / limit);

  return (
    <div className="datagrid-container">
      {/* Top controls */}
      <div className="datagrid-controls">
        <div>
          Show
          <select value={limit} onChange={(e) => setLimit(Number(e.target.value))}>
            {pageSizeOptions.map((n) => (
              <option key={n} value={n}>
                {n}
              </option>
            ))}
          </select>
          entries
        </div>

        <div>
          <input
            type="text"
            placeholder="Search..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
      </div>

      {/* Table */}
      <table className="datagrid-table">
        <thead>
          <tr>
            {columns.map((col) => (
              <th
                key={col.field}
                onClick={() => col.sortable && handleSort(col.field)}
                className={col.sortable ? "sortable" : ""}
              >
                {col.header}
                {sortField === col.field && (sortOrder === "asc" ? " 🔼" : " 🔽")}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading ? (
            <tr>
              <td colSpan={columns.length} align="center">
                Loading...
              </td>
            </tr>
          ) : data.length > 0 ? (
            data.map((row, i) => (
              <tr key={i}>
                {columns.map((col) => (
                  <td key={col.field}>
                    {col.render ? col.render(row[col.field], row) : row[col.field]}
                  </td>
                ))}
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={columns.length} align="center">
                No records found
              </td>
            </tr>
          )}
        </tbody>
      </table>

      {/* Pagination */}
      <div className="datagrid-pagination">
        <button disabled={page === 1} onClick={() => setPage(page - 1)}>
          Prev
        </button>
        <span>
          Page {page} of {totalPages || 1}
        </span>
        <button disabled={page === totalPages} onClick={() => setPage(page + 1)}>
          Next
        </button>
      </div>
    </div>
  );
};

export default DataGrid;
