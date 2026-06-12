import {
    CREATE_TASK, CREATE_TASK_SUCCESS, CREATE_TASK_FAILURE,
    LIST_TASKS,LIST_TASKS_SUCCESS,LIST_TASKS_FAILURE,
    DETAIL_TASK, DETAIL_TASK_FAILURE, DETAIL_TASK_SUCCESS,DETAIL_TASK_RESET,
    EDIT_TASK, EDIT_TASK_FAILURE, EDIT_TASK_SUCCESS, SET_TASK_DEFAULTS
} from '../actionTypes/TaskTypes';

import TaskService from './../services/TaskService';

function resetState() {
    dispatch({
        type: SET_TASK_DEFAULTS
    });
}

function createTask(page) {
    return function (dispatch, getState) {
        // start sending request (first dispatch)
        dispatch({
            type: CREATE_TASK
        });
        // async call must dispatch action whether on success or failure
        let data = TaskService.add(page);
        dispatch({
            type: CREATE_TASK_SUCCESS,
            data: data
        });
    }
}

function listAllTasks(module) {
    return function (dispatch, getState) {
        // start sending request (first dispatch)
        dispatch({
            type: LIST_TASKS
        });
        // async call must dispatch action whether on success or failure
        TaskService.listAll(module).then(response => {
            if(response.data.success === true){
                dispatch({
                    type: LIST_TASKS_SUCCESS,
                    data: response.data.payload.data
                });
            }
        }).catch(error => {
            dispatch({
                type: LIST_TASKS_FAILURE,
                error: error.response.data
            });
        });
    }
}

function detailTask(id) {
    return function (dispatch, getState) {
        // start sending request (first dispatch)
        /*dispatch({
            type: DETAIL_TASK_RESET
        });*/

        dispatch({
            type: DETAIL_TASK
        });
        // async call must dispatch action whether on success or failure
        TaskService.detail(id).then(response => {
            if(response.data.success === true){
                dispatch({
                    type: DETAIL_TASK_SUCCESS,
                    data: response.data.payload
                });
            }
        }).catch(error => {
            dispatch({
                type: DETAIL_TASK_FAILURE,
                error: error.response.data
            });
        });
    }
}

function editTask(id, values) {
    return function (dispatch, getState) {
        // start sending request (first dispatch)
        dispatch({
            type: EDIT_TASK
        });
        console.log(id);
        // async call must dispatch action whether on success or failure
        TaskService.edit(id, values).then(response => {
            if(response.data.success === true){
                dispatch({
                    type: EDIT_TASK_SUCCESS,
                    data: response.data.payload
                });
            }else{
                dispatch({
                    type: EDIT_TASK_FAILURE,
                    data: response.data.payload
                });
            }
        }).catch(error => {
            dispatch({
                type: EDIT_TASK_FAILURE,
                error: error.response.data
            });
        });
    }
}

function addTask(values) {
    return function (dispatch, getState) {
        // start sending request (first dispatch)
        dispatch({
            type: CREATE_TASK
        });
        // async call must dispatch action whether on success or failure
        TaskService.add(values).then(response => {
            if(response.data.success === true){
                dispatch({
                    type: CREATE_TASK_SUCCESS,
                    data: response.data.payload
                });
            }else{
                dispatch({
                    type: CREATE_TASK_FAILURE,
                    data: response.data.payload
                });
            }
        }).catch(error => {
            dispatch({
                type: CREATE_TASK_FAILURE,
                error: error.response.data
            });
        });
    }
}

export {
    createTask, listAllTasks, detailTask, editTask, addTask
};