import React, { useState } from 'react';
import { Modal, Box, Typography, TextField, Button, FormControl, InputLabel, Select, MenuItem } from '@mui/material';
import axios from 'axios';

const SignupModal = ({ open, onClose }) => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('');

  const handleSignup = async () => {
    const formData = new FormData();
    formData.append('action', 'signup');
    formData.append('name', name);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('role', role);

    try {
      const response = await axios.post('http://localhost/JobFinder/Backend/public/api.php', formData, { withCredentials: true });
      console.log(response.data);
      if (response.data.success) {
        alert(response.data.message);

        setName('');
        setEmail('');
        setPassword('');
        setRole('');
        
        onClose();
      } else {
        alert(response.data.message);
      }
    } catch (error) {
      console.error('Error signing up:', error);
      alert('An error occurred during signup.');
    }
  };

  return (
    <Modal open={open} onClose={onClose}>
      <Box className="p-8 bg-white rounded-md max-w-md mx-auto mt-20 shadow-md">
        <Typography className="text-xl font-bold text-gray-800 mb-4">Signup</Typography>
        <TextField
          label="Name"
          fullWidth
          margin="normal"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="bg-gray-100"
          InputLabelProps={{ className: "text-gray-700" }}
        />
        <TextField
          label="Email"
          fullWidth
          margin="normal"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          className="bg-gray-100"
          InputLabelProps={{ className: "text-gray-700" }}
        />
        <TextField
          label="Password"
          type="password"
          fullWidth
          margin="normal"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          className="bg-gray-100"
          InputLabelProps={{ className: "text-gray-700" }}
        />
        <FormControl fullWidth margin="normal" className="bg-gray-100">
          <InputLabel className="text-gray-700">Role</InputLabel>
          <Select value={role} onChange={(e) => setRole(e.target.value)} className="text-gray-800">
            <MenuItem value="job_seeker">Job Seeker</MenuItem>
            <MenuItem value="employer">Employer</MenuItem>
          </Select>
        </FormControl>
        <Button fullWidth onClick={handleSignup} className="mt-4 bg-gray-800 text-white hover:bg-gray-900">
          Signup
        </Button>
      </Box>
    </Modal>
  );
};

export default SignupModal;
