import React, { useState } from "react";
import { Modal, Form } from "react-bootstrap";
import { Formik, Field, Form as FormikForm, ErrorMessage } from "formik";
import * as Yup from "yup";
import axios from "axios";
import { useAlert } from "./../hooks/AlertContext";

/**
 * Inline "Create Product" button + modal for the Sales invoice page.
 * Mirrors AddStock's pattern: a small button rendered next to the Product label
 * that opens a popup. On success it calls onCreated({id, name}) so the parent can
 * add the new product to its list and auto-select it — no trip to the Products page.
 *
 * Only the important fields are shown (the Products page has more, but those are
 * optional and can be filled later by editing the product).
 */
function AddProduct({ onCreated, createApi, existingProducts }) {
  const [open, setOpen] = useState(false);
  const { showAlert } = useAlert();

  const initialValues = { name: "", selling_price: "", tax_vat: "", unit_weight: "", description: "" };

  // Names already in the product list — used to block duplicates before submitting.
  const existingNames = (existingProducts || []).map(p => String(p.name || "").trim().toLowerCase());

  const validationSchema = Yup.object({
    name: Yup.string().trim().required("Product name is required")
      .test("unique", "This product already exists", (value) =>
        !value || !existingNames.includes(value.trim().toLowerCase())),
    selling_price: Yup.number().typeError("Selling price must be a number").min(0, "Must be 0 or more").nullable(true),
    tax_vat: Yup.number().typeError("Tax/Vat must be a number").min(0, "Must be 0 or more").nullable(true),
    unit_weight: Yup.number().typeError("Unit weight must be a number").min(0, "Must be 0 or more").nullable(true),
  });

  const url = createApi || "/management/settings/import/entities/product-quick-add";

  const handleSubmit = async (values, { setSubmitting, resetForm, setFieldError }) => {
    try {
      const res = await axios.post(url, {
        name: values.name.trim(),
        selling_price: values.selling_price,
        tax_vat: values.tax_vat,
        unit_weight: values.unit_weight,
        description: values.description,
      });
      if (res.data.success && res.data.item) {
        // Safety net: the backend reuses an existing row on a name clash. If that
        // happens it means the product already existed — show it under the field
        // instead of silently adding a duplicate.
        if (res.data.reused) {
          setFieldError("name", "This product already exists");
          return;
        }
        if (typeof onCreated === "function") onCreated(res.data.item, res.data.reused);
        resetForm();
        setOpen(false);
      } else {
        showAlert(res.data.message || "Failed to create product", "error");
      }
    } catch (err) {
      showAlert(err.response?.data?.message || "Failed to create product", "error");
    } finally {
      setSubmitting(false);
    }
  };

  const fieldStyle = { fontSize: "13px" };

  return (
    <>
      <span
        className="add-product-button"
        style={{ borderRadius: "4px", color: "#fff", background: "#6c757d", cursor: "pointer", fontSize: "12px", padding: "4px 8px", display: "inline-block", fontWeight: 600 }}
        onClick={() => setOpen(true)}
        title="Create a new product"
      >
        <i className="fa fa-plus" style={{ marginRight: "3px" }}></i>Add
      </span>

      <Modal show={open} centered onHide={() => setOpen(false)}>
        <Modal.Header style={{ borderBottom: "2px solid #f97316", paddingBottom: "12px" }}>
          <Modal.Title style={{ fontSize: "16px", fontWeight: "700", color: "#f97316", letterSpacing: "0.3px" }}>
            Create New Product
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Formik initialValues={initialValues} validationSchema={validationSchema} onSubmit={handleSubmit}>
            {({ isSubmitting }) => (
              <FormikForm>
                <Form.Group className="mb-2">
                  <Form.Label style={fieldStyle}>Product Name <span style={{ color: "#ef4444" }}>*</span></Form.Label>
                  <Field type="text" name="name" autoFocus placeholder="Enter Product Name" className="form-control orange-input" />
                  <div className="text-danger" style={{ fontSize: "12px" }}><ErrorMessage name="name" /></div>
                </Form.Group>

                <div style={{ display: "flex", gap: "10px" }}>
                  <Form.Group className="mb-2" style={{ flex: 1 }}>
                    <Form.Label style={fieldStyle}>Selling Price</Form.Label>
                    <Field type="number" step="0.01" name="selling_price" placeholder="0.00" className="form-control orange-input" />
                    <div className="text-danger" style={{ fontSize: "12px" }}><ErrorMessage name="selling_price" /></div>
                  </Form.Group>
                  <Form.Group className="mb-2" style={{ flex: 1 }}>
                    <Form.Label style={fieldStyle}>Tax/Vat (%)</Form.Label>
                    <Field type="number" step="0.01" name="tax_vat" placeholder="0" className="form-control orange-input" />
                    <div className="text-danger" style={{ fontSize: "12px" }}><ErrorMessage name="tax_vat" /></div>
                  </Form.Group>
                </div>

                <Form.Group className="mb-2">
                  <Form.Label style={fieldStyle}>Unit Weight (kg)</Form.Label>
                  <Field type="number" step="0.01" name="unit_weight" placeholder="Enter Unit Weight" className="form-control orange-input" />
                  <div className="text-danger" style={{ fontSize: "12px" }}><ErrorMessage name="unit_weight" /></div>
                </Form.Group>

                <Form.Group className="mb-1">
                  <Form.Label style={fieldStyle}>Description</Form.Label>
                  <Field as="textarea" name="description" rows={2} placeholder="Short description (optional)" className="form-control orange-input" />
                </Form.Group>

                <div style={{ marginTop: "16px", display: "flex", justifyContent: "flex-end", gap: "8px" }}>
                  <button type="button" onClick={() => setOpen(false)} style={{ background: "#fff", border: "1px solid #ddd", color: "#555", padding: "7px 20px", borderRadius: "6px", fontSize: "13px", cursor: "pointer" }}>
                    Cancel
                  </button>
                  <button type="submit" disabled={isSubmitting} style={{ background: "#f97316", border: "none", color: "#fff", padding: "7px 20px", borderRadius: "6px", fontSize: "13px", fontWeight: "600", cursor: "pointer", opacity: isSubmitting ? 0.7 : 1 }}>
                    {isSubmitting ? "Saving..." : "Save Product"}
                  </button>
                </div>
              </FormikForm>
            )}
          </Formik>
        </Modal.Body>
      </Modal>
    </>
  );
}

export default AddProduct;
