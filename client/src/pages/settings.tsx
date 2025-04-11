import { useState, useEffect } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Save } from "lucide-react";

interface Setting {
  id: number;
  name: string;
  value: string;
  description: string | null;
}

export default function Settings() {
  const [institutionName, setInstitutionName] = useState("");
  const [institutionAddress, setInstitutionAddress] = useState("");
  const [institutionPhone, setInstitutionPhone] = useState("");
  const [institutionEmail, setInstitutionEmail] = useState("");
  const [receiptPrefix, setReceiptPrefix] = useState("");
  const [isSaving, setIsSaving] = useState(false);
  
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Fetch settings
  const { data: settings = [], isLoading } = useQuery({
    queryKey: ['/api/settings'],
  });

  useEffect(() => {
    if (settings.length > 0) {
      // Map settings to state variables
      settings.forEach((setting: Setting) => {
        switch (setting.name) {
          case 'institution_name':
            setInstitutionName(setting.value);
            break;
          case 'institution_address':
            setInstitutionAddress(setting.value);
            break;
          case 'institution_phone':
            setInstitutionPhone(setting.value);
            break;
          case 'institution_email':
            setInstitutionEmail(setting.value);
            break;
          case 'receipt_prefix':
            setReceiptPrefix(setting.value);
            break;
        }
      });
    }
  }, [settings]);

  const handleSaveGeneralSettings = async () => {
    setIsSaving(true);
    
    try {
      // Update institution name
      await apiRequest('PUT', '/api/settings/institution_name', { value: institutionName });
      
      // Update institution address
      await apiRequest('PUT', '/api/settings/institution_address', { value: institutionAddress });
      
      // Update institution phone
      await apiRequest('PUT', '/api/settings/institution_phone', { value: institutionPhone });
      
      // Update institution email
      await apiRequest('PUT', '/api/settings/institution_email', { value: institutionEmail });
      
      toast({
        title: "Settings saved",
        description: "Institution settings have been updated successfully",
      });
      
      queryClient.invalidateQueries({ queryKey: ['/api/settings'] });
    } catch (error) {
      console.error('Save settings error:', error);
      toast({
        title: "Error",
        description: "Failed to save settings. Please try again.",
        variant: "destructive",
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleSaveReceiptSettings = async () => {
    setIsSaving(true);
    
    try {
      // Update receipt prefix
      await apiRequest('PUT', '/api/settings/receipt_prefix', { value: receiptPrefix });
      
      toast({
        title: "Settings saved",
        description: "Receipt settings have been updated successfully",
      });
      
      queryClient.invalidateQueries({ queryKey: ['/api/settings'] });
    } catch (error) {
      console.error('Save settings error:', error);
      toast({
        title: "Error",
        description: "Failed to save settings. Please try again.",
        variant: "destructive",
      });
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <MainLayout title="Settings">
      <Tabs defaultValue="general">
        <TabsList className="mb-4">
          <TabsTrigger value="general">General</TabsTrigger>
          <TabsTrigger value="receipts">Receipts</TabsTrigger>
          <TabsTrigger value="backup">Backup & Restore</TabsTrigger>
        </TabsList>
        
        <TabsContent value="general">
          <Card>
            <CardHeader>
              <CardTitle>Institution Settings</CardTitle>
              <CardDescription>
                Configure your institution information for receipts and documents
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="institution-name">Institution Name</Label>
                <Input 
                  id="institution-name" 
                  value={institutionName} 
                  onChange={(e) => setInstitutionName(e.target.value)} 
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="institution-address">Address</Label>
                <Textarea 
                  id="institution-address" 
                  rows={3} 
                  value={institutionAddress} 
                  onChange={(e) => setInstitutionAddress(e.target.value)} 
                />
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="institution-phone">Phone Number</Label>
                  <Input 
                    id="institution-phone" 
                    value={institutionPhone} 
                    onChange={(e) => setInstitutionPhone(e.target.value)} 
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="institution-email">Email Address</Label>
                  <Input 
                    id="institution-email" 
                    type="email" 
                    value={institutionEmail} 
                    onChange={(e) => setInstitutionEmail(e.target.value)} 
                  />
                </div>
              </div>
              
              <div className="flex justify-end">
                <Button onClick={handleSaveGeneralSettings} disabled={isSaving}>
                  <Save className="mr-2 h-4 w-4" />
                  {isSaving ? "Saving..." : "Save Changes"}
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="receipts">
          <Card>
            <CardHeader>
              <CardTitle>Receipt Settings</CardTitle>
              <CardDescription>
                Configure how receipts are generated and numbered
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="receipt-prefix">Receipt Number Prefix</Label>
                <Input 
                  id="receipt-prefix" 
                  value={receiptPrefix} 
                  onChange={(e) => setReceiptPrefix(e.target.value)} 
                  placeholder="e.g., UIC-REC-"
                />
                <p className="text-xs text-gray-500">
                  This prefix will be added to all receipt numbers. Example: {receiptPrefix}12345
                </p>
              </div>
              
              <div className="flex justify-end">
                <Button onClick={handleSaveReceiptSettings} disabled={isSaving}>
                  <Save className="mr-2 h-4 w-4" />
                  {isSaving ? "Saving..." : "Save Changes"}
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="backup">
          <Card>
            <CardHeader>
              <CardTitle>Backup & Restore</CardTitle>
              <CardDescription>
                Backup your data or restore from a previous backup
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg">Backup Data</CardTitle>
                    <CardDescription>
                      Export all your data as a JSON file
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Button>
                      Download Backup
                    </Button>
                  </CardContent>
                </Card>
                
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg">Restore Data</CardTitle>
                    <CardDescription>
                      Import data from a backup file
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      <Input type="file" />
                      <Button variant="outline">
                        Upload & Restore
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              </div>
              
              <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4 text-yellow-800 text-sm">
                <p className="font-semibold">Warning:</p>
                <p>Restoring from backup will overwrite all existing data. Make sure to backup current data before restoring.</p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </MainLayout>
  );
}
