/*
import useResponsiveColumns from "./useResponsiveColumns";
const columnsConfig = {
    always: [
      { name: "ID", selector: row => row.id },   // <-- always visible
    ],
    large: [
      { name: "Name", selector: row => row.name },
      { name: "Email", selector: row => row.email },
      { name: "Phone", selector: row => row.phone },
      { name: "Address", selector: row => row.address },
    ],
    medium: [
      { name: "Name", selector: row => row.name },
      { name: "Email", selector: row => row.email },
    ],
    small: [
      { name: "Name", selector: row => row.name },
    ],
};
const { visibleCols, hiddenCols } = useResponsiveColumns(columnsConfig);
<DataTable
  columns={visibleCols}
  data={data}
  pagination
  highlightOnHover
  expandableRows={hiddenCols.length > 0}
  expandableRowsComponent={({ data }) => (
	<div style={{ padding: 12 }}>
	  {hiddenCols.map(col => (
		<p key={col.name}>
		  <strong>{col.name}:</strong> {col.selector(data)}
		</p>
	  ))}
	</div>
  )}
/>
*/
import { useState, useEffect } from "react";

export default function useResponsiveColumns(columnsConfig) {
  const { always = [], small = [], medium = [], large = [] } = columnsConfig;

  const [windowWidth, setWindowWidth] = useState(window.innerWidth);
  const [visibleCols, setVisibleCols] = useState([...always, ...large]);
  const [hiddenCols, setHiddenCols] = useState([]);

  useEffect(() => {
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  useEffect(() => {
    let baseCols = large;

    if (windowWidth < 600) baseCols = small;
    else if (windowWidth < 992) baseCols = medium;

    const finalVisible = [...always, ...baseCols];

    setVisibleCols(finalVisible);

    const finalHidden = large.filter(
      col => !finalVisible.some(v => v.selector === col.selector)
    );

    setHiddenCols(finalHidden);
  }, [windowWidth]);

  return { visibleCols, hiddenCols };
}
