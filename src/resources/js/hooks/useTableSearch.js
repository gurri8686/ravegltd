/**
 * import useTableSearch from "./hooks/useTableSearch";
 * const { filteredData, SearchInput } = useTableSearch(data, true);
 * {SearchInput}
	<DataTable
		columns={columns}
		data={filteredData}
		pagination
	/>
 */
import { useMemo, useState } from "react";

const useTableSearch = (data = [], deep = false) => {
    const [searchText, setSearchText] = useState("");

    const extractValues = (row) => {
        if (!deep) {
            return Object.values(row).join(" ");
        }

        // deep= true → flatten nested objects
        const flatten = (obj) =>
            Object.values(obj)
                .map(val =>
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

    // Search input component returned from hook
    const SearchInput = (
        <div style={{display:'flex',alignItems:'center',gap:'10px',marginBottom:'16px'}}>
            <div style={{position:'relative',flex:1}}>
                <i className="fa fa-search" style={{position:'absolute',left:'12px',top:'50%',transform:'translateY(-50%)',color:'#9ca3af',fontSize:'12px',zIndex:2,pointerEvents:'none'}}></i>
                <input
                    type="text"
                    placeholder="Search..."
                    className="form-control"
                    value={searchText}
                    onChange={(e) => setSearchText(e.target.value)}
                    style={{paddingLeft:'36px',height:'40px',borderRadius:'10px',border:'1.5px solid #e5e7eb',fontSize:'12px',background:'#f9fafb',boxShadow:'0 1px 3px rgba(0,0,0,0.04)'}}
                />
            </div>
        </div>
    );

    return { filteredData, SearchInput, searchText, setSearchText };
};

export default useTableSearch;
