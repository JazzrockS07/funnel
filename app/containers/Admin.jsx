import React, { Component } from 'react';
import PropTypes from 'prop-types';

import fetchWP from '../utils/fetchWP';

export default class Admin extends Component {
    constructor(props) {
        super(props);

        this.handleChangeForm = this.handleChangeForm.bind(this);
        this.handleChangeLinks = this.handleChangeLinks.bind(this);
        // Set the default states
        this.state = {
            postType: '',
            selPostType: '',
            titleName: '',
            forms:'',
            formsExist:'',
            formsNew: '',
            links: '',
            linksExist:'',
            linksNew: '',
            views: 0,
            visitors: 0,
            refferals: '',
            description: '',
            opacity:false,
            funnel: false,
            edit: false,
            message: false
        };

        this.fetchWP = new fetchWP({
            restURL: this.props.wpObject.api_url,
            restNonce: this.props.wpObject.api_nonce,
        });

        // Get the currently set email address from our /admin endpoint and update the email state accordingly
        this.getSetting();
    }

    getSetting = () => {
        this.fetchWP.get( 'admin' )
            .then(
                (json) => this.setState({
                    postType: json.value,
                }),
                (err) => console.log( 'error', err )
            );
    };

    processOkResponse = (json, action) => {
        if (json.success) {
            this.setState({
                forms: json.forms,
                formsExist: json.exist_forms,
                formsNew: json.pre_forms,
                links: json.links,
                linksExist: json.exist_links,
                linksNew: json.pre_links,
                titleName: json.name,
                views: json.views,
                visitors: json.visitors,
                refferals: json.url,
                funnel: true,
            });
        }
    }

    handleChange = (event) => {
        this.setState({
            selPostType:event.target.value,
        });
    }

    handleChangePage = (event) => {
        this.setState({
            selectPage:event.target.value,
        });
    }

    handleSubmit = (event) => {
        event.preventDefault();
        this.fetchWP.post( 'admin', { post_type: this.state.selPostType, page: this.state.selectPage } )
            .then(
                (json) => this.processOkResponse(json, 'saved'),
                (err) => this.setState({
                        message: err.message, // The error message returned by the REST API
                })
            );
    }

    handleChangeTitle = (event) => {
        this.setState( {
           newName:event.target.value,
        });
    }

    handleChangeLinks(event) {
        const {name, value} = event.target;
        //const numb = name.substr(4,1);
        const link = name.substr(4,1);
        console.log(name);
        console.log(value);
        console.log(this.state.linksNew);
        this.state.linksNew[link] = value;

        this.setState({
            [name]: value
        })
    }

    handleChangeForm(event)  {
        //console.log(this.state.forms);
        const {name, value} = event.target;
        //console.log(name);
        //console.log(value);
        //console.log(id);
        //const old_forms = this.state.forms;
        const number = name.substr(4, 1);
        //console.log (number);

        this.state.formsNew[number] = value;
        //console.log(this.state.new);
        //console.log(this.state.forms);
        //console.log(this.state.links);



        this.setState({
            [name]: value
        })

        //console.log(this.state.forms);
        //console.log(old_forms);
        //console.log(this.state.newForms);
        /*this.setState({
            newForms: {
                [0]:event.target.value,
                [1]:'test',
                [2]:'test2'
            }
        })
        console.log(this.state.newForms);

         */
    }

    handleChangeDescription = (event) => {
        this.setState( {
           descript:event.target.value,
        });
    }

    handleEditSubmit = (event) => {
        event.preventDefault();
        let new_name;
        console.log('test');
        console.log(this.state.formsExist);
        console.log(this.state.formsNew);
        if (this.state.newName === this.state.titleName) {
            new_name = false;
        } else {
            new_name = this.state.newName;
        }
        this.setState( {
            description: this.state.descript,
            edit:false,
            funnel:true,
        })

        this.fetchWP.post('submission', {
            post_type: this.state.selPostType,
            page: this.state.selectPage,
            new_name: new_name,
            exist_forms: this.state.formsExist,
            new_forms: this.state.formsNew,
            exist_links: this.state.linksExist,
            new_links: this.state.linksNew,
            description: this.state.descript,
        })
            .then (
                (json) =>this.processOkResponseNew(json,'saved'),
                (err) => this.setState( {
                    message:err.message,
                })
            );
    }

    processOkResponseNew = (json, action) => {
        if (json.success) {
            this.setState({
                titleName: json.name,
                forms: json.forms,
                formsExist: json.exist_forms,
                formsNew: json.pre_forms,
                links: json.links,
                linksExist: json.exist_links,
                linksNew: json.pre_links,
            });
        }
    }


    handleDelete = () => {
        this.setState({
            funnel:false
        })
    }

    handleEdit = () => {
        this.setState(( {
            edit:true,
            newName:this.state.titleName,
            funnel:false,
        }))
    }

    handleCancel = () => {
        this.setState(( {
            edit:false,
            funnel:true,
        }))
    }

    render() {
        let funnel;
        let form;
        let message;
        let edit;

        //console.log(this.state.postType);
        //console.log(this.state.postType[this.state.typePage]);
        //console.log(this.state.forms);
        //console.log(this.state.links);
        console.log(this.state.formsNew);
        console.log(this.state.linksNew);

        if (this.state.message) {
            message = <div>{this.state.message}</div>
        }

        form = <form>
            <select value={this.state.value} onChange={this.handleChange} className={'form-select'}>
                <option value="none" hidden>post_type</option>
                {Object.keys(this.state.postType).map(type => (
                    <option key={type} value={type}>
                        {type}
                    </option>
                ))}
            </select>
            <select onChange={this.handleChangePage} className={'form-select'}>
                <option value="none" hidden>page</option>
                {Object.keys(this.state.postType).map(ptype => (
                    ptype === this.state.selPostType &&
                    Object.keys(this.state.postType[this.state.selPostType]).map(number => (
                        <option key={number} value={this.state.postType[this.state.selPostType][number]['ID']}>
                            {this.state.postType[this.state.selPostType][number]['post_title']}
                        </option>
                    ))
                )) }
            </select>

            <button
                id="select-post"
                className="button button-primary"
                onClick={this.handleSubmit}
            >Submit</button>
        </form>

        if ( this.state.funnel) {
            funnel = <div className={'funnel-block'}>
                <div className={'clearfix funnel-header'}>
                    <div className={'funnel-page'}>
                        {this.state.titleName}
                    </div>
                    <div className={'funnel-delete'} onClick={this.handleDelete}>
                        Delete
                    </div>
                    <div className={'funnel-edit'} onClick={this.handleEdit}>
                        Edit
                    </div>
                </div>
                <div className={'funnel-fields'}>Description:</div>
                <div className={'funnel-description'}>{this.state.description}</div>
                <div className={'funnel-fields'}>Forms:</div>
                {Object.keys(this.state.forms).map(forms => (
                    <div className={'funnel-forms'} key={'forms' + forms}>
                        {this.state.forms[forms]}
                    </div>
                ))}
                <div className={'funnel-fields'}>URL's:</div>
                {Object.keys(this.state.links).map(number => (
                    <div className={'funnel-links'} key={number}>
                        <a href={this.state.links[number]['href']}>{this.state.links[number]['text']}</a>
                    </div>
                ))}
                <div className={'clearfix funnel-footer'}>
                    <div className={'footer-icon-edit'} onClick={this.handleEdit}>
                        <span className="dashicons dashicons-edit-large dashicons-size"></span>
                    </div>
                    <div className={'footer-icon-link'}>
                        <span className="dashicons dashicons-admin-links dashicons-size"></span>
                    </div>
                    <div className={'footer-refferals tooltip'}>
				                    refferals
                            <span className={"tooltiptext"}>
                                    {Object.keys(this.state.refferals).map(number => (
                                        <div key={number}>
                                            {this.state.refferals[number]}
                                        </div>
                                    ))}
                            </span>
                    </div>
                    <div className={'footer-views'}>
                        <span className="dashicons dashicons-visibility dashicons-size"></span>
                        <span>{this.state.views}</span>
                    </div>
                    <div className={'footer-visitors'}>
                        <span className="dashicons dashicons-admin-users dashicons-size"></span>
                        <span>{this.state.visitors}</span>
                    </div>
                </div>
            </div>
            form = ''
        }

        if (this.state.edit) {
            edit = <div className={'edit-block'}>
                <div className={'clearfix edit-header'}>
                    <div className={'edit-page'}>
                        {this.state.titleName}
                    </div>
                    <div className={'edit-cancel'} onClick={this.handleCancel}>
                        Cancel
                    </div>
                </div>
                <form onSubmit={this.handleSubmit}>
                    <div className={'edit-form-title'}>
                        <span className={'edit-form-title-label'}>
                            Title:
                        </span>
                            <input className={'edit-form-title-input'}
                                type="text"
								size={38}
                                name="edit-title"
                                   value={this.state.newName}
                                onChange={this.handleChangeTitle}
                            />
                    </div>

                    <div className={'clearfix edit-form-forms'}>
                        <div className={'edit-form-forms-label'}>Forms:</div>
                        <div className={'edit-form-forms-block'}>
                            {Object.keys(this.state.formsNew).map(form => (
                                <input
                                    id = {'form' + form}
                                    key = {'form' + form}
                                    type = "text"
                                    size = {38}
                                    className = {'edit-form-forms-input'}
                                    name = {'form' + form}
                                    onChange = {this.handleChangeForm}
                                    value = {this.state.formsNew[form]}
                                />
                            ))
                            }
                        </div>
                    </div>
                    <div className={'clearfix edit-form-links'}>
                        <div className={'edit-form-links-label'}>URL's</div>
                        <div className={'edit-form-links-block'}>
                            {Object.keys(this.state.linksNew).map(number => (
                                <input
                                    id = {'link' + number}
                                    key={'link' + number}
                                    type="text"
                                    size={38}
                                    className={'edit-form-links-input'}
                                    name={'link' + number}
                                    onChange={this.handleChangeLinks}
                                    value = {this.state.linksNew[number]}
                                />
                            ))
                            }
                        </div>
                    </div>
                    <div className={'edit-form-description'}>
                        <span className={'edit-form-description-label'}>
                            Description:
                        </span>
                        <textarea
                            name="description"
                            cols={41}
                            rows={3}
                            onChange={this.handleChangeDescription}
                        />
                    </div>
                    <div className={'edit-form-button'}>
                        <button
                            className="button button-primary"
                            onClick={this.handleEditSubmit}
                        >Submit</button>
                    </div>
                </form>
                <div className={'clearfix edit-footer'}>
                    <div className={'footer-icon-link'}>
                        <span className="dashicons dashicons-admin-links dashicons-size"></span>
                    </div>
                </div>
            </div>
            form = ''
        }

        return (
            <div className="wrap">
                {message}
                {funnel}
                {form}
                {edit}
            </div>
        );
    }
}

Admin.propTypes = {
    wpObject: PropTypes.object
};