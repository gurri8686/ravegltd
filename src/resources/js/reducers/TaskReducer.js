import {
    CREATE_TASK, CREATE_TASK_FAILURE, CREATE_TASK_SUCCESS, LIST_TASKS, LIST_TASKS_FAILURE, LIST_TASKS_SUCCESS, SET_TASK_DEFAULTS,
    DETAIL_TASK, DETAIL_TASK_FAILURE, DETAIL_TASK_SUCCESS,DETAIL_TASK_RESET,
    EDIT_TASK, EDIT_TASK_FAILURE, EDIT_TASK_SUCCESS,
    ADD_TASK, ADD_TASK_FAILURE, ADD_TASK_SUCCESS
} from '../actionTypes/TaskTypes';

const initialState = {
    tasks: [],
    task: "",
    success_message: "",
    error_message: "",
    validation_errors: null,
    list_spinner: false,
    create_update_spinner: false
};

const TaskReducer = function (state = initialState, action) {
    switch (action.type) {
        case CREATE_TASK:
            return {
                ...state,
                list_spinner: true,
                error_message : "",
                success_message : ""
            }
        case CREATE_TASK_SUCCESS:
            return {
                ...state,
                list_spinner: false,
                error_message : "",
                success_message : action.data
            }
        case CREATE_TASK_FAILURE:
            return {
                ...state,
                list_spinner: false,
                error_message : action.data,
                success_message : ""
            }
        
        /**
         * Edit submission starts. 
         */    
        case EDIT_TASK:
            return {
                ...state,
                list_spinner: true,
                error_message : "",
                success_message : ""
            };    

        case EDIT_TASK_SUCCESS:
            return {
                ...state,
                //task:action.data,
                list_spinner: false,
                error_message : "",
                success_message: action.data
            };
        
        case EDIT_TASK_FAILURE:
            return {
                ...state,
                list_spinner : false,
                error_message : action.data,
                success_message : ""
            };
        /**
         * Edit Form submission ends. 
         */


        /**
         * Edit submission starts. 
         */    
         case DETAIL_TASK_RESET:
            return {
                ...state,
                task:"",
                list_spinner: false,
                error_message : "",
                success_message: ""
            };
         case DETAIL_TASK:
            return {
                ...state,
                list_spinner: true,
                error_message : "",
                success_message : ""
            };    

        case DETAIL_TASK_SUCCESS:
            return {
                ...state,
                task:action.data,
                list_spinner: false,
                error_message : "",
                success_message: ""
            };
        
        case DETAIL_TASK_FAILURE:
            return {
                ...state,
                list_spinner : false,
                error_message : action.data,
                success_message : ""
            };
        /**
         * Edit Form submission ends. 
         */
        case LIST_TASKS:
            return {
                ...state,
                list_spinner: true
            };
        case LIST_TASKS_SUCCESS:
            return {
                ...state,
                list_spinner: false,
                tasks : action.data
            };
        case LIST_TASKS_FAILURE:
            
        case SET_TASK_DEFAULTS:
            return {
                ...state,
                task: { ...state.task },
                success_message: "",
                error_message: "",
                validation_errors: null,
                list_spinner: false,
                create_update_spinner: false
            };
        default:
            return state;
    }
};
export default TaskReducer;