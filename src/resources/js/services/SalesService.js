import axios from "axios";
const SalesService = {
    productsList: () => {
        return axios.get('/data_entry/sales_entry/ajax/product-list');
    },
    paymentsList: () => {
        return axios.get('/data_entry/sales_entry/ajax/payment-list');
    },
	suppliersList: (product_id) => {
        return axios.get('/data_entry/sales_entry/ajax/supplier-list/'+product_id);
    },
	suppliersListAll: () => {
        return axios.get('/data_entry/sales_entry/ajax/supplier-list-all');
    },
    addInvoice: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/add-invoice', values);
    },
    addSingleInvoice: (values) => {
       return axios.post('/data_entry/sales_entry/ajax/add-single-invoice', { ...values, price: Number(values.price) || 0, totalPrice: Number(values.totalPrice) || 0 });
    },
    deleteSingleInvoice: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/delete-single-invoice', values);
     },
     invoiceDetail: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/fetch-invoice-detail', values);
     },
     allInvoiceDetail: (id) => {
      return axios.get('/data_entry/sales_entry/ajax/invoice-detail/'+ id);
   },
     editSingleInvoice: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/edit-single-invoice', { ...values, price: Number(values.price) || 0, totalPrice: Number(values.totalPrice) || 0 });
     },
     updatePaymentMethod: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/update-payment', values);
     },
     updateInvoiceDetailMethod: (values) => {
        return axios.post('/data_entry/sales_entry/ajax/update-invoice-detail', values);
     },
     FetchUser: (values) => {
        return axios.get('/data_entry/sales_entry/ajax/fetchuser');
     },
	 productSupplierInvoices: (product_id, supplier_id) => {
      return axios.get('/data_entry/sales_entry/ajax/product-supplier-invoices/'+ product_id+'/'+supplier_id);
   },

};
export default SalesService;
