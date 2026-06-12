import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Routes, Route, Link, MemoryRouter, HashRouter } from "react-router-dom";
import { ReactNotifications } from 'react-notifications-component';

export default function TaskApp(props) {

    return (
        <>
          Hello World!
        </>
        
    );
}

if (document.getElementById('react-task-app')) {
    const id = "react-task-app";
    const root = createRoot(document.getElementById(id));
    const element = document.getElementById(id);
    const props = Object.assign({}, element.dataset)
    root.render(
		  <TaskApp {...props} />
    );
}