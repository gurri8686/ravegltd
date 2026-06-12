function NotFound() {
    return (<>
            <div className="row">
                <div className="col-md-12">
                    <div className="error-template">
                        <h1>
                            Oops!</h1>
                        <h2>
                            404 Not Found</h2>
                        <div className="error-details">
                            Sorry, an error has occured, You don't have access!
                        </div>
                        <div className="error-actions mt-4">
                            <a href="http://www.jquery2dotnet.com" className="btn btn-default btn-lg"><span className="glyphicon glyphicon-envelope"></span> Contact Support </a>
                        </div>
                    </div>
                </div>
            </div>
    </>)
}

export default NotFound;