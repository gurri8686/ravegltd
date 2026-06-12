import axios from "axios";
const PurchasesService = {
    productsList: () => {
        return axios.get('/data_entry/purchase_entry/ajax/product-list');
    },    
    addInvoice: (values) => {
        return axios.post('/data_entry/purchase_entry/ajax/add-invoice', values);
    },
    addSingleInvoice: (values) => {
       return axios.post('/data_entry/purchase_entry/ajax/add-single-invoice', values);
    },
    deleteSingleInvoice: (values) => {
        return axios.post('/data_entry/purchase_entry/ajax/delete-single-invoice', values);
     },
     invoiceDetail: (values) => {
        return axios.post('/data_entry/purchase_entry/ajax/fetch-invoice-detail', values);
     },
	 invoiceStock: (values) => {
        return axios.post('/data_entry/purchase_entry/ajax/add-invoice-stock', values);
     },
     editSingleInvoice: (values) => {
        return axios.post('/data_entry/purchase_entry/ajax/edit-single-invoice', values);
     },
    allInvoiceDetail: (id) => {
      return axios.get('/data_entry/purchase_entry/ajax/invoice-detail/'+ id);
	},
	deleteInvoiceProducts: (values) => {
      return axios.post('/data_entry/purchase_entry/ajax/delete-invoice-products', values);
	},
};
export default PurchasesService;