import React, { useEffect, useState } from "react";
import { Formik, Form, Field } from "formik";
import { createRoot } from 'react-dom/client';
import axios from "axios";
import { Container, Row, Col, Button, Spinner } from "react-bootstrap";

const GeneralSettings = () => {
  const [settings, setSettings] = useState([]);
  const [loading, setLoading] = useState(true);

  // ✅ Load settings from Laravel API
  useEffect(() => {
    axios.get("/general_settings/view/list")
      .then((response) => {
        setSettings(response.data); // should be array of {id, setting, status}
      })
      .catch((error) => console.error("Error fetching settings:", error))
      .finally(() => setLoading(false));
  }, []);

  // ✅ Handle submit
  const handleSubmit = (values, { setSubmitting }) => {
    axios.post("/general-settings/save", values)
      .then(() => alert("Settings saved successfully!"))
      .catch((error) => console.error("Error saving settings:", error))
      .finally(() => setSubmitting(false));
  };

  {/*if (loading) return <div className="text-center mt-5"><Spinner animation="border" /></div>;*/}

  // Build Formik initial values dynamically
  const initialValues = settings.reduce((acc, item) => {
    acc[item.setting] = item.status === 1;
    return acc;
  }, {});

  return (
	  <div className="row">
		<div className="col-12">
			132132132
		</div>
	  </div>
  );
};

export default GeneralSettings;

// ----------------- Mount App -----------------
if (document.getElementById('general-settings-app')) {
    const id = "general-settings-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
			<GeneralSettings {...props} />
    );
}