// useDiggPagination.js
import { useState, useMemo } from "react";

// Hook
export default function useDiggPagination(data = [], perPage = 10, pagesToShow = 5) {
    const [currentPage, setCurrentPage] = useState(1);

    const totalPages = Math.ceil(data.length / perPage);

    const pageData = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        return data.slice(start, start + perPage);
    }, [data, currentPage, perPage]);

    // Generate Digg-style pagination numbers
    const pageNumbers = useMemo(() => {
        const pages = [];
        const half = Math.floor(pagesToShow / 2);

        let start = Math.max(1, currentPage - half);
        let end = Math.min(totalPages, start + pagesToShow - 1);

        if (end - start < pagesToShow - 1) {
            start = Math.max(1, end - pagesToShow + 1);
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        return pages;
    }, [currentPage, totalPages, pagesToShow]);

    // UI Component to render directly
    const Pagination = () => (
        <div className="digg-pagination mt-2">
            <button
                className="page-btn"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(1)}
            >
                « First
            </button>

            <button
                className="page-btn"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(currentPage - 1)}
            >
                ‹ Prev
            </button>

            {pageNumbers.map((num) => (
                <button
                    key={num}
                    className={`page-number ${num === currentPage ? "active" : ""}`}
                    onClick={() => setCurrentPage(num)}
                >
                    {num}
                </button>
            ))}

            <button
                className="page-btn"
                disabled={currentPage === totalPages}
                onClick={() => setCurrentPage(currentPage + 1)}
            >
                Next ›
            </button>

            <button
                className="page-btn"
                disabled={currentPage === totalPages}
                onClick={() => setCurrentPage(totalPages)}
            >
                Last »
            </button>
        </div>
    );

    return { currentPage, setCurrentPage, totalPages, pageData, Pagination };
}
