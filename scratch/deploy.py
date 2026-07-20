import os
import zipfile
import paramiko

def zip_project(zip_path):
    print("Zipping project...")
    exclude_dirs = {'.git', 'node_modules', 'vendor', 'postgres-data', '.gemini', 'mobile'}
    exclude_files = {'blackdoor.zip', 'postgres.log', 'deploy.py', 'ssh_exec.py', 'ssh_deploy.py', '.env', '.env.production', 'control_private_key.pem', 'control_public_key.pem'}
    
    count = 0
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk('.'):
            # Remove excluded dirs in-place to avoid walking down them
            dirs[:] = [d for d in dirs if d not in exclude_dirs]
            
            for file in files:
                if file in exclude_files:
                    continue
                # Exclude any custom logos
                if file.startswith('custom_'):
                    continue
                    
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, '.')
                
                # Check for temp branding directories and skip
                if 'temp_branding_' in rel_path:
                    continue
                
                zipf.write(full_path, rel_path)
                count += 1
                
    print(f"Zipped {count} files successfully.")

def upload_and_extract():
    host = "100.70.240.43"
    port = 22
    username = "itcloud"
    password = "clone1997"
    local_zip = "blackdoor.zip"
    remote_zip = "/home/itcloud/blackdoor.zip"
    
    # 1. Zip
    zip_project(local_zip)
    
    # 2. Upload
    print("Uploading zip file to remote server...")
    transport = paramiko.Transport((host, port))
    try:
        transport.connect(username=username, password=password)
        sftp = paramiko.SFTPClient.from_transport(transport)
        sftp.put(local_zip, remote_zip)
        print("Upload completed.")
        sftp.close()
    finally:
        transport.close()
        
    # 3. Extract and restart
    print("Extracting zip on remote server...")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        client.connect(host, port, username, password)
        
        # Unzip command
        cmd_unzip = "unzip -q -o /home/itcloud/blackdoor.zip -d /home/itcloud/blackdoor"
        stdin, stdout, stderr = client.exec_command(cmd_unzip)
        err = stderr.read().decode('utf-8')
        if err:
            print(f"Unzip warning/error: {err}")
            
        print("Cleaning up remote zip...")
        client.exec_command("rm -f /home/itcloud/blackdoor.zip")
        
        print("Successfully extracted code on server.")
        
    finally:
        client.close()
        
    # Clean up local zip
    if os.path.exists(local_zip):
        os.remove(local_zip)
        print("Cleaned up local zip file.")

if __name__ == "__main__":
    upload_and_extract()
