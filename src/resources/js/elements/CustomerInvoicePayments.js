import React, {
  useEffect,
  useRef,
  useState,
  useImperativeHandle,
  forwardRef,
} from "react";
import Select from "react-select";
import Button from "react-bootstrap/Button";
import { ToastContainer, toast } from "react-toastify";
import { Modal, Accordion, Dropdown, DropdownButton } from "react-bootstrap";
import DataTable from "react-data-table-component";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import axios from "axios";

import { formatTwoDecimal } from "./../hooks/utils";
import { useWindowSize } from "./../hooks/useWindowSize";

// ✅ Validation schema
const PaymentSchema = Yup.object().shape({
  payment_method: Yup.string().required("Please select a payment method"),
  amount: Yup.number()
    .typeError("Amount must be a number")
    .positive("Amount must be greater than zero")
    .required("Amount is required"),
  note: Yup.string().max(200, "Note cannot exceed 200 characters"),
});

// ✅ Main Component
export default function CustomerInvoicePayments(props) {
  const [activeKey, setActiveKey] = useState(null);
  const gridRef = useRef();

  const handleToggle = (key) => {
    setActiveKey(activeKey === key ? null : key);
  };

  // Function called by child form on change or submit
  const handleFormChange = () => {
    if (gridRef.current) {
      gridRef.current.reloadData(); // 👈 call grid's exposed function
    }
  };

  return (
    <>
      <ToastContainer autoClose={3000} />
      <Accordion activeKey={activeKey}>
        <Accordion.Item eventKey="0" className="border-0 text-right">
          
          {/*<Accordion.Header onClick={() => handleToggle("0")} className="text-right">
            <span className="fw-bold bg-info border-0">Invoice Payments</span>
            <span className="ms-auto bg-info" style={{ fontSize: "1.1rem" }}>
              {activeKey === "0" ? "▲" : "▼"}
            </span>
          </Accordion.Header>*/}

          <button className="btn btn-info" onClick={() => handleToggle("0")}>Make Invoice Payments <i className="fa fa-arrow-circle-o-down"></i></button>

          <Accordion.Body className="bg-white text-dark p-2 mt-1 text-left">
            <div className="row">
              <div className="col-3">
                <PaymentForm {...props} onFormChange={handleFormChange} />
              </div>
              <div className="col-9">
                <PaymentGrid ref={gridRef} {...props} />
              </div>
            </div>
          </Accordion.Body>
        </Accordion.Item>
      </Accordion>
    </>
  );
}

// ✅ Payment Form Component
function PaymentForm(props) {
  const notifyError = (error) =>
    toast.error(error, {
      position: "bottom-right",
      autoClose: 3000,
      theme: "light",
    });

  const notifySuccess = (success) =>
    toast.success(success, {
      position: "bottom-right",
      autoClose: 3000,
      theme: "light",
    });

  const handleSubmit = async (values, { setSubmitting, resetForm }) => {
    try {
      values.id = props.id;
      values.customer = props.customer;

      const response = await axios.post(
        "/data_entry/sales_entry/invoice_payment/create",
        values
      );

      if (response.data.success === true) {
        notifySuccess("Payment saved successfully!");
        resetForm();
        if (props.onFormChange) props.onFormChange(); // ✅ trigger grid reload
      } else if (response.data.success === false) {
        notifyError(response.data.payload);
      }
    } catch (error) {
      console.error("Error saving payment:", error);
      notifyError("Something went wrong while saving.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="max-w-md mx-auto">
      <h4 className="mb-2 text-left">
        Make Payment: {props.currency} {props.total}
      </h4>

      <Formik
        initialValues={{
          payment_method: "",
          amount: "",
          note: "",
        }}
        validationSchema={PaymentSchema}
        onSubmit={handleSubmit}
      >
        {({ isSubmitting }) => (
          <Form>
            {/* Payment Method */}
            <div className="mb-2">
              <label className="form-label">Payment Method</label>
              <Field as="select" name="payment_method" className="form-control">
                <option value="">Select method</option>
                <option value="2">Cash</option>
                <option value="4">Card</option>
                <option value="5">Bank Transfer</option>
                <option value="3">Check</option>
              </Field>
              <ErrorMessage
                name="payment_method"
                component="div"
                className="text-danger small"
              />
            </div>

            {/* Amount */}
            <div className="mb-2">
              <label className="form-label">Amount to Pay</label>
              <Field
                type="number"
                name="amount"
                className="form-control"
                placeholder="Enter amount"
              />
              <ErrorMessage
                name="amount"
                component="div"
                className="text-danger small"
              />
            </div>

            {/* Note */}
            <div className="mb-2">
              <label className="form-label">Note</label>
              <Field
                as="textarea"
                name="note"
                className="form-control"
                rows="3"
                placeholder="Enter a note (optional)"
              />
              <ErrorMessage
                name="note"
                component="div"
                className="text-danger small"
              />
            </div>

            {/* Submit Button */}
            
			{props.total > 0
			?
			<button
              type="submit"
              className="btn btn-info text-white text-right"
              disabled={isSubmitting}
            >
              {isSubmitting ? "Saving..." : "Save Payment"}
            </button>
			:
			<></>
			}
			
          </Form>
        )}
      </Formik>
    </div>
  );
}

// ✅ Payment Grid Component
const PaymentGrid = forwardRef((props, ref) => {
  const [payments, setPayments] = useState([]);
  const [details, setDetails] = useState([]);
  const [loading, setLoading] = useState(false);
  
  const notifyError = (error) =>
    toast.error(error, {
      position: "bottom-right",
      autoClose: 3000,
      theme: "light",
    });

  const notifySuccess = (success) =>
    toast.success(success, {
      position: "bottom-right",
      autoClose: 3000,
      theme: "light",
    });


  const fetchPaymentData = async (id) => {
    try {
      setLoading(true);
      const response = await axios.get(
        `/data_entry/sales_entry/invoice_payment/view/${id}`
      );
      setPayments(response.data.payload.list || []);
	  setDetails(response.data.payload.details || []);
    } catch (error) {
      console.error("Error fetching payment data:", error);
    } finally {
      setLoading(false);
    }
  };
  
  const removePaymentData = async (id, customer_id, customer_invoice_id) => {
    //alert(id+'-'+customer_id+'-'+customer_invoice_id)
	try {
      setLoading(true);
      const response = await axios.post(
        `/data_entry/sales_entry/invoice_payment/delete`, {id:id,customer_id:customer_id,customer_invoice_id}
      );
      //setPayments(response.data.payload.list || []);
	  //setDetails(response.data.payload.details || []);
	  
	  if (response.data.success === true) {
        notifySuccess("Payment removed successfully!");
        fetchPaymentData(props.id)
		
      } else if (response.data.success === false) {
		notifyError(response.data.payload);
		
      }
	  
    } catch (error) {
      console.error("Error fetching payment data:", error);
    } finally {
      setLoading(false);
    }
  };

  // 👇 expose function to parent
  useImperativeHandle(ref, () => ({
    reloadData: () => fetchPaymentData(props.id),
  }));

  useEffect(() => {
    fetchPaymentData(props.id);
  }, [props.id]);

  const columns = [
    { name: "ID", selector: (row) => row.id, sortable: true, width: "70px" },
    { name: "Amount", selector: (row) => row.amount, sortable: true },
	{ name: "Credit", selector: (row) => row.credit, sortable: true },
	{ name: "Discounted?", selector: (row) => row.is_discounted, sortable: true },
	{ name: "Refunded?", selector: (row) => row.is_refunded, sortable: true },
	{ name: "Method", selector: (row) => row.payment_id, sortable: false },
    { name: "Note", selector: (row) => row.note || "-", wrap: true },
    { name: "Date", selector: (row) => row.created_at || "-", wrap: true },
    {
      name: "Actions",
      cell: (row) => (
        <DropdownButton
          id={`dropdown-${row.id}`}
          title="Options"
          size="sm"
          variant="secondary"
          drop="start"
        >
          <Dropdown.Item onClick={() => removePaymentData(row.id, row.customer_id, row.customer_invoice_id)}>
            Delete
          </Dropdown.Item>
        </DropdownButton>
      ),
      ignoreRowClick: true,
      allowOverflow: true,
      button: true,
    },
  ];

  return (
    <div className="container">
      <h4 className="mb-2">{details.type}: {props.currency} {details.paid}</h4>
      <DataTable
        columns={columns}
        data={payments}
        pagination
        highlightOnHover
        striped
        responsive
        persistTableHead
        progressPending={loading}
      />
    </div>
  );
});
