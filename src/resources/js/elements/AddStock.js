import React,{ useEffect, useRef, useState } from "react";
import { Modal, Button, Form } from "react-bootstrap";
import { Formik, Field, Form as FormikForm, ErrorMessage } from "formik";
import Select from 'react-select';
import { orangeSelectStyles } from './../utils/selectStyles';
import * as Yup from "yup";
import axios from "axios";
import SalesService from "./../services/SalesService";
import PurchasesService from "./../services/PurchasesService";
import {AlertProvider, useAlert } from "./../hooks/AlertContext";

const selectStyles = {
  control: (base, state) => ({
    ...base,
    borderColor: state.isFocused ? '#f97316' : '#ced4da',
    boxShadow: state.isFocused ? '0 0 0 0.2rem rgba(249,115,22,0.25)' : 'none',
    '&:hover': { borderColor: '#f97316' },
  }),
  option: (base, state) => ({
    ...base,
    backgroundColor: state.isSelected ? '#f97316' : state.isFocused ? '#fff3e0' : '#fff',
    color: state.isSelected ? '#fff' : '#333',
    cursor: 'pointer',
  }),
};

function AddStock({ show, onClose, product, index, apiKey, onSaveStock, invoiceId }) {

	const [open, setOpen] = useState(show);
	const [allSuppliers, setAllSuppliers] = useState([]);
	const { showAlert } = useAlert();
	
	// Form initial values
	const initialValues = {
		product: product,
		supplier: "",
		quantity: product?.quantity || "",
		price: product?.price || "",
	};
  
  useEffect(() => {
  }, []);
  
  const openPopup = () => {
	SalesService.suppliersListAll().then(response => {
		if(response.data.success=== true){
			const formattedSuppliers = response.data.payload.map((supplier) => ({
			  label: supplier.name,     // change 'name' to your actual field
			  value: supplier.id || supplier.value, // adjust field
			}));

			// Update state
		setAllSuppliers(formattedSuppliers);
		}
    });
	setOpen(true)
  }
  
  const closePopup = () => {
	setOpen(false)
  }

  // Form validation schema
  const validationSchema = Yup.object({
    supplier: Yup.mixed().required("Supplier is required"),
    quantity: Yup.number()
      .typeError("Quantity must be a number")
      .required("Quantity is required")
      .positive("Quantity must be greater than 0"),
    price: Yup.number()
      .typeError("Price must be a number")
      .required("Price is required")
      .positive("Price must be greater than 0"),
  });

  // Submit handler
  const handleSubmit = async (values, { setSubmitting, resetForm }) => {
    try {
      const payload = {
        //product_id: product.id,
        index: index,
        ...values,
      };
	  
	  //console.log(payload); return;
	  
	const response = await PurchasesService.invoiceStock(payload);
	if(response.data.success === true){
		//showAlert("Purchase Invoice Added  Successfully!", "success");
		onSaveStock(index, response.data.payload)
		closePopup();
	}if(response.data.success === false){
		onSaveStock(index, "")
		showAlert(response.data.payload, "error");
	}

      setSubmitting(false);
	  
    } catch (error) {
      console.error("API Error:", error);
      setSubmitting(false);
    }
  };

  return (
  <>
	<span className="add-stock-button" style={{borderRadius:'4px',color:'#fff',background:'#6c757d',cursor:'pointer',fontSize:'12px',padding:'4px 8px',display:'inline-block'}} onClick={openPopup}><i className="fa fa-plus" style={{marginRight:'3px'}}></i>Stock</span>
    <Modal show={open} centered>
      <Modal.Header style={{borderBottom:'2px solid #f97316',paddingBottom:'12px'}}>
        <Modal.Title style={{fontSize:'16px',fontWeight:'700',color:'#f97316',letterSpacing:'0.3px'}}>
          Add Stock Invoice
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p>
          <strong>Product Name:</strong> {product?.label || "-"}
        </p>
        {/*<p>
          <strong>Product ID:</strong> {product?.value || "-"}
        </p>*/}

        <Formik
          initialValues={initialValues}
          validationSchema={validationSchema}
          onSubmit={handleSubmit}
        >
          {({ isSubmitting,setFieldValue, values }) => (
            <FormikForm>
              <Form.Group className="mb-1">
                <Form.Label>Supplier</Form.Label>
                <Select options={allSuppliers}
					styles={orangeSelectStyles}
					//defaultValue={supplier_id}
					//isDisabled={fieldToggle}
					onChange={(evnt) => setFieldValue("supplier", evnt)}
					name="supplier"
				/>
                <div className="text-danger">
                  <ErrorMessage name="supplier" />
                </div>
              </Form.Group>

              <Form.Group className="mb-1">
                <Form.Label>Quantity</Form.Label>
                <Field
                  type="number"
                  name="quantity"
                  className="form-control orange-input"
                />
                <div className="text-danger">
                  <ErrorMessage name="quantity" />
                </div>
              </Form.Group>

              <Form.Group className="mb-1">
                <Form.Label>Price</Form.Label>
                <Field type="number" name="price" className="form-control orange-input" />
                <div className="text-danger">
                  <ErrorMessage name="price" />
                </div>
              </Form.Group>

			<div style={{marginTop:'16px',display:'flex',justifyContent:'flex-end',gap:'8px'}}>
              <button type="button" onClick={closePopup} style={{background:'#fff',border:'1px solid #ddd',color:'#555',padding:'7px 20px',borderRadius:'6px',fontSize:'13px',cursor:'pointer'}}>
                Cancel
              </button>
              <button type="submit" disabled={isSubmitting} style={{background:'#f97316',border:'none',color:'#fff',padding:'7px 20px',borderRadius:'6px',fontSize:'13px',fontWeight:'600',cursor:'pointer',opacity:isSubmitting?0.7:1}}>
                {isSubmitting ? "Submitting..." : "Submit"}
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

export default AddStock;
