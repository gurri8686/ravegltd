import { combineReducers } from 'redux';
import TaskReducer from './TaskReducer';

const rootReducer = combineReducers({
    TaskReducer: TaskReducer
});
export default rootReducer;