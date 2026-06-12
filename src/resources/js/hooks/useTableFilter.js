/**
 * import useTableFilter from "./hooks/useTableFilter";
 * const filteredData = useTableFilter(data, searchText, true);
	<input
		type="text"
		placeholder="Search..."
		className="form-control mb-3"
		value={searchText}
		onChange={(e) => setSearchText(e.target.value)}
	/>

 */
import { useMemo } from "react";

const useTableFilter = (data = [], searchText = "", deep = false) => {
    
    const extractValues = (row) => {
        if (!deep) {
            return Object.values(row).join(" ");
        }

        // If deep=true flatten nested objects
        const flatten = (obj) =>
            Object.values(obj)
                .map((val) =>
                    typeof val === "object" && val !== null
                        ? flatten(val)
                        : val
                )
                .flat();

        return flatten(row).join(" ");
    };

    const filteredData = useMemo(() => {
        if (!searchText.trim()) return data;

        return data.filter((row) =>
            extractValues(row)
                .toLowerCase()
                .includes(searchText.toLowerCase())
        );
    }, [data, searchText]);

    return filteredData;
};

export default useTableFilter;
