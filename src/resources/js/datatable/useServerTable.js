// src/hooks/useServerTable.js
import { useState, useEffect, useCallback } from "react";
import axios from "axios";

export default function useServerTable(apiUrl, initialParams = {}) {
    const [data, setData] = useState([]);
    const [totalRows, setTotalRows] = useState(0);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(initialParams.perPage || 10);
    const [search, setSearch] = useState('');
    const [sortField, setSortField] = useState('id');
    const [sortOrder, setSortOrder] = useState('desc');

    const fetchData = useCallback(async (overridePage = page) => {
        setLoading(true);
        try {
            const response = await axios.get(apiUrl, {
                params: {
                    search,
                    sortField,
                    sortOrder,
                    perPage,
                    page: overridePage,
                    ...initialParams,
                },
            });
            setData(response.data.data || []);
            setTotalRows(response.data.total || 0);
        } catch (error) {
            console.error("Failed to fetch table data:", error);
        } finally {
            setLoading(false);
        }
    }, [apiUrl, search, sortField, sortOrder, perPage, page, initialParams]);

    useEffect(() => {
        fetchData(1); // reset to page 1 on filter/sort/search
    }, [search, sortField, sortOrder, perPage, fetchData]);

    const handlePageChange = (newPage) => {
        setPage(newPage);
        fetchData(newPage);
    };

    const handlePerRowsChange = (newPerPage, newPage) => {
        setPerPage(newPerPage);
        fetchData(newPage);
    };

    const handleSort = (column, sortDirection) => {
        setSortField(column.selector);
        setSortOrder(sortDirection);
    };

    return {
        data,
        totalRows,
        loading,
        page,
        perPage,
        search,
        setSearch,
        handlePageChange,
        handlePerRowsChange,
        handleSort,
        refresh: fetchData,
    };
}
