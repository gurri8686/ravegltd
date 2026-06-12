import { useMemo } from "react";

/**
 * Custom hook to parse query data from props, DOM, or URL
 * 
 * @param {string|object} [queryInput] - Optional JSON string or object (e.g. props.query)
 * @param {string} [elementId] - Optional element ID for fallback
 * @returns {object} Parsed query data
 * import { useQueryData } from "./../hooks/useQueryData";
 * const query = useQueryData(props.query);
 */
export function useQueryData(queryInput, elementId = "stock-closing-app") {
  return useMemo(() => {
    let queryData = {};

    // 1️⃣ If provided via props
    if (queryInput) {
      if (typeof queryInput === "string") {
        try {
          queryData = JSON.parse(queryInput);
        } catch {
          console.warn("Invalid JSON in queryInput:", queryInput);
        }
      } else if (typeof queryInput === "object") {
        queryData = queryInput;
      }
    }

    // 2️⃣ If not found, check DOM element
    if (!Object.keys(queryData).length) {
      const el = document.getElementById(elementId);
      if (el?.dataset.query) {
        try {
          queryData = JSON.parse(el.dataset.query);
        } catch {
          console.warn("Failed to parse data-query from element:", elementId);
        }
      }
    }

    // 3️⃣ Fallback to URL query params
    if (!Object.keys(queryData).length) {
      const params = new URLSearchParams(window.location.search);
      params.forEach((value, key) => (queryData[key] = value));
    }

    return queryData;
  }, [queryInput, elementId]);
}
