#define MyAppName "Balafon Broadcast Manager"
#define MyAppVersion "0.1.0"
#define MyAppPublisher "Balafon"
#define MyAppExeName "cmd.exe"

[Setup]
AppId={{C3F34E66-94D1-45A7-A8AC-D80B4C243B9B}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\Balafon
DisableProgramGroupPage=yes
PrivilegesRequired=admin
OutputDir=..\..\dist\installer
OutputBaseFilename=Balafon-Setup
Compression=lzma
SolidCompression=yes
WizardStyle=modern

[Files]
Source: "..\..\dist\windows-release\Balafon\*"; DestDir: "{app}"; Flags: recursesubdirs createallsubdirs ignoreversion

[Icons]
Name: "{autoprograms}\Balafon\Install Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\install_windows_tester.cmd"""
Name: "{autoprograms}\Balafon\Start Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\start_windows_local.cmd"""
Name: "{autoprograms}\Balafon\Stop Balafon"; Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\stop_windows_local.cmd"""

[Run]
Filename: "{#MyAppExeName}"; Parameters: "/c ""{app}\scripts\install_windows_tester.cmd"""; Flags: nowait postinstall skipifsilent
