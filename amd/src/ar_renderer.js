// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Encapsules the behavior for creating a Wavefront 3D model in Moodle.
 *
 * Manages the UI while operations are occuring, including rendering and manipulating the model.
 *
 * @module     mod_wavefront/ar_renderer
 * @class      ar_renderer
 * @package    mod_wavefront
 * @copyright  2022 Ian Wild
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.9
 */

import WebGL from 'mod_wavefront/WebGL';
import * as THREE from 'mod_wavefront/three';
import { MTLLoader } from 'mod_wavefront/MTLLoader';
import { OBJLoader } from 'mod_wavefront/OBJLoader';
import { OrbitControls } from 'mod_wavefront/OrbitControls';
import { ARButton } from 'mod_wavefront/ARButton';
import jQuery from 'jquery';

let camera, scene, renderer;
let controller;

init();
animate();

function onWindowResize() {

	camera.aspect = window.innerWidth / window.innerHeight;
	camera.updateProjectionMatrix();

	renderer.setSize( window.innerWidth, window.innerHeight );

}

function animate() {

	renderer.setAnimationLoop( render );

}

function render() {

	renderer.render( scene, camera );

}

export const init = () => {

	const container = document.createElement( 'div' );
	document.body.appendChild( container );

	scene = new THREE.Scene();

	camera = new THREE.PerspectiveCamera( 70, window.innerWidth / window.innerHeight, 0.01, 20 );

	const light = new THREE.HemisphereLight( 0xffffff, 0xbbbbff, 1 );
	light.position.set( 0.5, 1, 0.25 );
	scene.add( light );

	//

	renderer = new THREE.WebGLRenderer( { antialias: true, alpha: true } );
	renderer.setPixelRatio( window.devicePixelRatio );
	renderer.setSize( window.innerWidth, window.innerHeight );
	renderer.xr.enabled = true;
	container.appendChild( renderer.domElement );

	//

	document.body.appendChild( ARButton.createButton( renderer ) );

	//

	const geometry = new THREE.CylinderGeometry( 0, 0.05, 0.2, 32 ).rotateX( Math.PI / 2 );

	function onSelect() {

		const material = new THREE.MeshPhongMaterial( { color: 0xffffff * Math.random() } );
		const mesh = new THREE.Mesh( geometry, material );
		mesh.position.set( 0, 0, - 0.3 ).applyMatrix4( controller.matrixWorld );
		mesh.quaternion.setFromRotationMatrix( controller.matrixWorld );
		scene.add( mesh );

	}

	controller = renderer.xr.getController( 0 );
	controller.addEventListener( 'select', onSelect );
	scene.add( controller );

	//

	window.addEventListener( 'resize', onWindowResize );

};

		